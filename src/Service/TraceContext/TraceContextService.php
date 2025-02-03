<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Service\TraceContext;

use DR\SymfonyTraceBundle\Generator\TraceContext\TraceContextIdGenerator;
use DR\SymfonyTraceBundle\Service\TraceServiceInterface;
use DR\SymfonyTraceBundle\TraceContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
class TraceContextService implements TraceServiceInterface
{
    public const HEADER_TRACEPARENT   = 'traceparent';
    public const HEADER_TRACESTATE    = 'tracestate';
    public const HEADER_TRACERESPONSE = 'traceresponse';

    public function __construct(private readonly TraceContextIdGenerator $generator)
    {
    }

    public function supports(Request $request): bool
    {
        if ($request->headers->has(self::HEADER_TRACEPARENT) === false) {
            return false;
        }

        return TraceContextParser::isValid($request->headers->get(self::HEADER_TRACEPARENT, ''));
    }

    public function createNewTrace(): TraceContext
    {
        $trace = new TraceContext();
        $trace->setTraceId($this->generator->generateTraceId());
        $trace->setTransactionId($this->generator->generateTransactionId());

        return $trace;
    }

    public function createTraceFrom(string $traceParent): TraceContext
    {
        if (TraceContextParser::isValid($traceParent) === false) {
            return $this->createNewTrace();
        }

        $trace = TraceContextParser::parseTraceContext($traceParent, '');
        $trace->setTransactionId($this->generator->generateTransactionId());

        return $trace;
    }

    public function getRequestTrace(Request $request): TraceContext
    {
        $traceParent = $request->headers->get(self::HEADER_TRACEPARENT, '');
        $traceState  = $request->headers->get(self::HEADER_TRACESTATE, '');

        $trace = TraceContextParser::parseTraceContext($traceParent, $traceState);
        $trace->setTransactionId($this->generator->generateTransactionId());

        return $trace;
    }

    /**
     * @codeCoverageIgnore
     */
    public function handleResponse(Response $response, TraceContext $context): void
    {
        if ($context->getTraceId() !== null) {
            $response->headers->set(self::HEADER_TRACERESPONSE, $this->renderTraceParent($context));
        }
    }

    public function handleClientRequest(TraceContext $trace, string $method, string $url, array $options = []): array
    {
        if ($trace->getTraceId() === null) {
            return $options;
        }

        $options['headers'][self::HEADER_TRACEPARENT] = $this->renderTraceParent($trace);

        $traceState = $this->renderTraceState($trace);
        if ($traceState !== '') {
            $options['headers'][self::HEADER_TRACESTATE] = $traceState;
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
