<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Service;

use DR\SymfonyTraceBundle\Generator\TraceContext\TraceContextIdGenerator;
use DR\SymfonyTraceBundle\TraceContext;
use DR\SymfonyTraceBundle\TraceId;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
class TraceContextService implements TraceServiceInterface
{
    public const HEADER_TRACEPARENT = 'traceparent';
    public const HEADER_TRACESTATE  = 'tracestate';

    public function __construct(private readonly TraceContextIdGenerator $generator)
    {
    }

    public function supports(Request $request): bool
    {
        if ($request->headers->has(self::HEADER_TRACEPARENT) === false) {
            return false;
        }

        $traceParent = $request->headers->get(self::HEADER_TRACEPARENT, '');

        return preg_match('/^[0-9a-f]{2}-[0-9a-f]{32}-[0-9a-f]{16}-[0-9a-f]{2}$/i', $traceParent) === 1;
    }

    public function createNewTrace(): TraceContext
    {
        $trace = new TraceContext();
        $trace->setTraceId($this->generator->generateTraceId());
        $trace->setTransactionId($this->generator->generateTransactionId());

        return $trace;
    }

    public function getRequestTrace(Request $request): TraceContext
    {
        $traceParent  = $request->headers->get(self::HEADER_TRACEPARENT, '');
        $traceState   = $request->headers->get(self::HEADER_TRACESTATE, '');

        $trace = TraceContextParser::parseTraceContext($traceParent, $traceState);
        $trace->setTransactionId($this->generator->generateTransactionId());

        return $trace;
    }

    /**
     * @codeCoverageIgnore
     */
    public function handleResponse(Response $response, TraceId|TraceContext $context): void
    {
        // Do nothing
    }

    public function handleClientRequest(TraceId|TraceContext $trace, string $method, string $url, array $options = []): array
    {
        if ($trace instanceof TraceContext) {
            $traceParent = $this->renderTraceParent($trace);
            $options['headers'][self::HEADER_TRACEPARENT] = $traceParent;

            $traceState = $this->renderTraceState($trace);
            if ($traceState !== '') {
                $options['headers'][self::HEADER_TRACESTATE] = $traceState;
            }
        }

        return $options;
    }

    private function renderTraceParent(TraceContext $trace): string
    {
        return sprintf(
            '%s-%s-%s-%s',
            $trace->getVersion(),
            $trace->getTraceId(),
            $trace->getTransactionId(),
            $trace->getFlags()
        );
    }

    private function renderTraceState(TraceContext $traceContext): string
    {
        $traceState = [];
        foreach ($traceContext->getTraceState() as $key => $value) {
            $traceState[] = sprintf('%s=%s', $key, $value);
        }

        return implode(',', $traceState);
    }
}
