<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Generator;

/**
 * Generates new (hopefully) unique ID's for incoming requests, as transactionId and if lacking as traceId.
 */
interface TraceIdGeneratorInterface
{
    public function generateTransactionId(): string;
    public function generateTraceId(): string;
}
