<?php

declare(strict_types=1);

namespace DR\SymfonyRequestId\Generator\TraceContext;

use Exception;

/**
 * Generator class that generates trace values according to the tracecontext spec.
 * @internal
 */
class TraceContextIdGenerator
{
    public function generateTransactionId(): string
    {
        return bin2hex(random_bytes(16));
    }

    public function generateTraceId(): string
    {
        return bin2hex(random_bytes(16));
    }
}
