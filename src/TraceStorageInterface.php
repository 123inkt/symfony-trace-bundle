<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle;

/**
 * Stores the identifiers for the transaction.
 */
interface TraceStorageInterface
{
    public function getTransactionId(): ?string;
    public function setTransactionId(?string $id): void;

    public function getTraceId(): ?string;
    public function setTraceId(?string $id): void;

    public function getTrace(): TraceId|TraceContext;
    public function setTrace(TraceId|TraceContext $trace): void;
}
