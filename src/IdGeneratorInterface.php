<?php

declare(strict_types=1);

namespace DR\SymfonyRequestId;

/**
 * Generates new (hopefully) unique ID's for incoming requests, as transactionId and if lacking as traceId.
 */
interface IdGeneratorInterface
{
    /**
     * Create a new request ID.
     */
    public function generate(): string;
}
