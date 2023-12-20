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
        $this->hub->configureScope(
            static function (Scope $scope) use ($id) {
                if ($id === null) {
                    $scope->removeTag('transaction_id');

                    return;
                }
                $scope->setTag('transaction_id', $id);
            }
        );
    }

    public function getTraceId(): ?string
    {
        return $this->storage->getTraceId();
    }

    public function setTraceId(?string $id): void
    {
        $this->storage->setTraceId($id);
        $this->hub->configureScope(
            static function (Scope $scope) use ($id) {
                if ($id === null) {
                    $scope->removeTag('trace_id');

                    return;
                }
                $scope->setTag('trace_id', $id);
            }
        );
    }

    public function getTrace(): TraceContext
    {
        return $this->storage->getTrace();
    }

    public function setTrace(TraceContext $trace): void
    {
        $this->storage->setTrace($trace);
    }
}
