<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Generator\TraceContext;

use DR\SymfonyTraceBundle\Generator\TraceIdGeneratorInterface;
use Exception;

/**
 * Generator class that generates trace values according to the tracecontext spec.
 * @internal
 */
class TraceContextIdGenerator implements TraceIdGeneratorInterface
{
    /**
     * This is the ID of this request as known by the caller
     * (in some tracing systems, this is known as the span-id, where a span is the execution of a client request).
     */
    public function generateTransactionId(): string
    {
        return substr(bin2hex(random_bytes(16)), 16);
    }

    /**
     * This is the ID of the whole trace forest and is used to uniquely identify a distributed trace through a system.
     */
    public function generateTraceId(): string
    {
        return substr(bin2hex(random_bytes(32)), 32);
    }
}
