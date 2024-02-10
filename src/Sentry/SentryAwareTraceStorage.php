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
        $propagationContext = $scope->getPropagationContext();
        $sentryTraceId      = self::tryCreateTraceId($traceId);
        if ($sentryTraceId !== null) {
            $propagationContext->setTraceId($sentryTraceId);
        } elseif ($traceId !== null) {
            $scope->setTag('trace_id', $traceId);
        }

        if ($traceId === null) {
            $scope->removeTag('trace_id');
        }
    }

    private static function updateTransactionId(Scope $scope, ?string $transactionId): void
    {
        $propagationContext  = $scope->getPropagationContext();
        $sentryTransactionId = self::tryCreateSpanId($transactionId);
        if ($sentryTransactionId !== null) {
            $propagationContext->setSpanId($sentryTransactionId);
        } elseif ($transactionId !== null) {
            $scope->setTag('transaction_id', $transactionId);
        }

        if ($transactionId === null) {
            $scope->removeTag('transaction_id');
        }
    }

    private static function updatePropagationContext(Scope $scope, TraceContext $traceContext): void
    {
        $propagationContext = $scope->getPropagationContext();

        // set trace id
        self::updateTraceId($scope, $traceContext->getTraceId());

        // set transaction id
        self::updateTransactionId($scope, $traceContext->getTransactionId());

        // set parent transaction id
        $parentTransactionId = self::tryCreateSpanId($traceContext->getParentTransactionId());
        if ($parentTransactionId !== null) {
            $propagationContext->setParentSpanId($parentTransactionId);
        } elseif ($traceContext->getParentTransactionId() !== null) {
            $scope->setTag('parent_transaction_id', $traceContext->getParentTransactionId());
        }

        if ($traceContext->getParentTransactionId() === null) {
            $scope->removeTag('parent_transaction_id');
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
            // the span id format is not supported by Sentry
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
            // the trace id format is not supported by Sentry
            return null;
        }
    }
}
