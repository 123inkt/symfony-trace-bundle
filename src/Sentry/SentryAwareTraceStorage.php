<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Sentry;

use DR\SymfonyTraceBundle\TraceContext;
use DR\SymfonyTraceBundle\TraceStorageInterface;
use Sentry\State\HubInterface;
use Sentry\State\Scope;

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
        $this->hub->configureScope(
            static function (Scope $scope) use ($trace) {
                self::updateTraceId($scope, $trace->getTraceId());
                self::updateTraceId($scope, $trace->getTransactionId());
            }
        );
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
}
