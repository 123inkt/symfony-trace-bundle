<?php
declare(strict_types=1);

namespace DR\SymfonyTraceBundle;

/**
 * And ID storage backed by a property, simple.
 */
final class SimpleIdStorage implements IdStorageInterface
{
    private ?string $transactionId = null;
    private ?string $traceId = null;

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    public function setTransactionId(?string $id): void
    {
        $this->transactionId = $id;
    }

    public function getTraceId(): ?string
    {
        return $this->traceId;
    }

    public function setTraceId(?string $id): void
    {
        $this->traceId = $id;
    }
}
