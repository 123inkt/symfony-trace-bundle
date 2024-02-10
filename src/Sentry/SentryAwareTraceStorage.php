<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Sentry;

use DR\SymfonyTraceBundle\TraceContext;
use DR\SymfonyTraceBundle\TraceStorageInterface;
use InvalidArgumentException;
use Sentry\State\HubInterface;
use Sentry\State\Scope;
use Sentry\Tracing\SpanId;
use Sentry\Tracing\TraceId;

/**
 * @internal
 */
class SentryAwareTraceStorage implements TraceStorageInterface
{
    public function __construct(private readonly TraceStorageInterface $storage, private readonly HubInterface $hub)
    {
    }

    public function getTransactionId(): ?string
    {
        return $this->storage->getTransactionId();
    }

    public function setTransactionId(?string $id): void
    {
        $this->storage->setTransactionId($id);
        $this->hub->configureScope(static fn(Scope $scope) => self::updateTransactionId($scope, $id));
    }

    public function getTraceId(): ?string
    {
        return $this->storage->getTraceId();
    }

    public function setTraceId(?string $id): void
    {
        $this->storage->setTraceId($id);
        $this->hub->configureScope(static fn(Scope $scope) => self::updateTraceId($scope, $id));
    }

    public function getTrace(): TraceContext
    {
        return $this->storage->getTrace();
    }

    public function setTrace(TraceContext $trace): void
    {
        $this->storage->setTrace($trace);
        $this->hub->configureScope(static fn(Scope $scope) => self::updatePropagationContext($scope, $trace));
    }

    private static function updateTraceId(Scope $scope, ?string $traceId): void
    {
        if ($traceId === null) {
            $scope->removeTag('trace_id');
        } else {
            $scope->setTag('trace_id', $traceId);
        }
    }

    private static function updateTransactionId(Scope $scope, ?string $transactionId): void
    {
        if ($transactionId === null) {
            $scope->removeTag('transaction_id');
        } else {
            $scope->setTag('transaction_id', $transactionId);
        }
    }

    private static function updatePropagationContext(Scope $scope, TraceContext $traceContext): void
    {
        $propagationContext = $scope->getPropagationContext();

        // set trace id
        $traceId = self::tryCreateTraceId($traceContext->getTraceId());
        if ($traceId !== null) {
            $propagationContext->setTraceId($traceId);
        } else {
            self::updateTraceId($scope, $traceContext->getTraceId());
        }

        // set transaction id
        $transactionId = self::tryCreateSpanId($traceContext->getTransactionId());
        if ($transactionId !== null) {
            $propagationContext->setSpanId($transactionId);
        } else {
            self::updateTransactionId($scope, $traceContext->getTransactionId());
        }

        // set parent transaction id
        $parentTransactionId = self::tryCreateSpanId($traceContext->getParentTransactionId());
        if ($parentTransactionId !== null) {
            $propagationContext->setParentSpanId($parentTransactionId);
        } elseif ($traceContext->getParentTransactionId() === null) {
            $scope->removeTag('parent_transaction_id');
        } else {
            $scope->setTag('parent_transaction_id', $traceContext->getParentTransactionId());
        }
    }

    private static function tryCreateSpanId(?string $spanId): ?SpanId
    {
        if ($spanId === null) {
            return null;
        }

        try {
            return new SpanId($spanId);
        } catch (InvalidArgumentException) {
            // the current span id is not supported by Sentry
            return null;
        }
    }

    private static function tryCreateTraceId(?string $traceId): ?TraceId
    {
        if ($traceId === null) {
            return null;
        }

        try {
            return new TraceId($traceId);
        } catch (InvalidArgumentException) {
            // the current trace id is not supported by Sentry
            return null;
        }
    }
}
