<?php

declare(strict_types=1);

namespace DR\SymfonyRequestId\Service;

use DR\SymfonyRequestId\TraceContext;
use InvalidArgumentException;

/**
 * @internal
 */
class TraceContextService
{
    public const HEADER_TRACEPARENT = 'traceparent';
    public const HEADER_TRACESTATE  = 'tracestate';

    public function validateTraceParent(string $traceParent): bool
    {
        return preg_match('/^[0-9a-f]{2}-[0-9a-f]{32}-[0-9a-f]{16}-[0-9a-f]{2}$/i', $traceParent) === 1;
    }

    public function renderTraceParent(TraceContext $traceContext): string
    {
        return sprintf(
            '%s-%s-%s-%s',
            $traceContext->getVersion(),
            $traceContext->getTraceId(),
            $traceContext->getTransactionId(),
            $traceContext->getFlags()
        );
    }

    public function renderTraceState(TraceContext $traceContext): string
    {
        $traceState = [];
        foreach ($traceContext->getTraceState() as $key => $value) {
            $traceState[] = sprintf('%s=%s', $key, $value);
        }

        return implode(',', $traceState);
    }

    public function parseTraceContext(string $traceParent, string $traceState): TraceContext
    {
        $traceContext = $this->parseTraceParent($traceParent);
        $traceContext->setTraceState($this->parseTraceState($traceState));

        return $traceContext;
    }

    private function parseTraceParent(string $traceParent): TraceContext
    {
        $parts = explode('-', $traceParent);
        if (count($parts) !== 4) {
            throw new InvalidArgumentException('Invalid traceparent header');
        }

        return new TraceContext($parts[0], $parts[1], $parts[2], $parts[3]);
    }

    /**
     * @return array<string, string>
     */
    private function parseTraceState(string $traceState): array
    {
        $vendorStates = explode(',', $traceState);
        $vendorStates = array_map('trim', $vendorStates);
        $vendorStates = array_filter($vendorStates);

        $result = [];
        foreach ($vendorStates as $item) {
            $item = explode('=', $item);
            if (count($item) !== 2) {
                continue;
            }

            $result[$item[0]] = $item[1];
        }

        return $result;
    }
}
