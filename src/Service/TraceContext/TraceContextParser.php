<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Service\TraceContext;

use DR\SymfonyTraceBundle\TraceContext;
use InvalidArgumentException;

/**
 * @internal
 */
class TraceContextParser
{
    public static function isValid(string $traceParent): bool
    {
        return preg_match('/^[0-9a-f]{2}-[0-9a-f]{32}-[0-9a-f]{16}-[0-9a-f]{2}$/i', $traceParent) === 1;
    }

    public static function parseTraceContext(string $traceParent, string $traceState): TraceContext
    {
        $traceContext = self::parseTraceParent($traceParent);
        $traceContext->setTraceState(self::parseTraceState($traceState));

        return $traceContext;
    }

    private static function parseTraceParent(string $traceParent): TraceContext
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
    private static function parseTraceState(string $traceState): array
    {
        $vendorStates = explode(',', $traceState);
        $vendorStates = array_map('trim', $vendorStates);
        $vendorStates = array_filter($vendorStates, static fn(string $value) => $value !== '');

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
