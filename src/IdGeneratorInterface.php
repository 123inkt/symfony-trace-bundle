<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle;

/**
 * Generates new (hopefully) unique ID's for incoming requests, as transactionId and if lacking as traceId.
 */
interface IdGeneratorInterface
{
    /**
     * Create a new ID.
     */
    public function generate(): string;
}
