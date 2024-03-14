<?php
declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Service\TraceContext;

use DR\SymfonyTraceBundle\TraceContext;

class TraceContextRenderer
{
    public static function renderTraceParent(TraceContext $trace): string
    {
        return sprintf(
            '%s-%s-%s-%s',
            $trace->getVersion(),
            $trace->getTraceId(),
            $trace->getTransactionId(),
            $trace->getFlags()
        );
    }

    public static function renderTraceState(TraceContext $traceContext): string
    {
        $traceState = [];
        foreach ($traceContext->getTraceState() as $key => $value) {
            $traceState[] = sprintf('%s=%s', $key, $value);
        }

        return implode(',', $traceState);
    }
}
