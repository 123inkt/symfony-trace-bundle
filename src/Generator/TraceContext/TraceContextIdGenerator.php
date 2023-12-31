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
    public function generateTransactionId(): string
    {
        return substr(bin2hex(random_bytes(8)), 8);
    }

    public function generateTraceId(): string
    {
        return substr(bin2hex(random_bytes(16)), 16);
    }
}
