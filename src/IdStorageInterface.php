<?php

declare(strict_types=1);

namespace DR\SymfonyRequestId;

/**
 * Stores the identifiers for the transaction.
 */
interface IdStorageInterface
{
    public function getTransactionId(): ?string;
    public function setTransactionId(?string $id): void;

    public function getTraceId(): ?string;
    public function setTraceId(?string $id): void;
}
