<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Service\TraceId;

use DR\SymfonyTraceBundle\Generator\TraceIdGeneratorInterface;
use DR\SymfonyTraceBundle\Service\TraceServiceInterface;
use DR\SymfonyTraceBundle\TraceContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
class TraceIdService implements TraceServiceInterface
{
    public function __construct(
        private readonly string $requestHeader,
        private readonly string $responseHeader,
        private readonly string $clientHeader,
        private readonly TraceIdGeneratorInterface $generator,
    ) {
    }

    public function supports(Request $request): bool
    {
        return $request->headers->has($this->requestHeader);
    }

    public function createNewTrace(): TraceContext
    {
        $trace = new TraceContext();
        $trace->setTraceId($this->generator->generateTraceId());
        $trace->setTransactionId($this->generator->generateTransactionId());

        return $trace;
    }

    public function createTraceFrom(string $traceId): TraceContext
    {
        $trace = new TraceContext();
        $trace->setTraceId($traceId);
        $trace->setTransactionId($this->generator->generateTransactionId());

        return $trace;
    }

    public function getRequestTrace(Request $request): TraceContext
    {
        $trace = new TraceContext();
        $trace->setTraceId($request->headers->get($this->requestHeader));
        $trace->setTransactionId($this->generator->generateTransactionId());

        return $trace;
    }

    public function handleResponse(Response $response, TraceContext $context): void
    {
        if ($context->getTraceId() !== null) {
            $response->headers->set($this->responseHeader, $context->getTraceId());
        }
    }

    public function handleClientRequest(TraceContext $trace, string $method, string $url, array $options = []): array
    {
        $options['headers'][$this->clientHeader] ??= $trace->getTraceId();

        return $options;
    }
}
