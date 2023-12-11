<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Service;

use DR\SymfonyTraceBundle\Generator\TraceId\TraceIdGeneratorInterface;
use DR\SymfonyTraceBundle\TraceContext;
use DR\SymfonyTraceBundle\TraceId;
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

    public function createNewTrace(): TraceId
    {
        $trace = new TraceId();
        $trace->setTraceId($this->generator->generate());
        $trace->setTransactionId($this->generator->generate());

        return $trace;
    }

    public function getRequestTrace(Request $request): TraceId
    {
        $trace = new TraceId();
        $trace->setTraceId($request->headers->get($this->requestHeader));
        $trace->setTransactionId($this->generator->generate());

        return $trace;
    }

    public function handleResponse(Response $response, TraceId|TraceContext $context): void
    {
        if ($context->getTraceId() !== null) {
            $response->headers->set($this->responseHeader, $context->getTraceId());
        }
    }

    public function handleClientRequest(TraceId|TraceContext $trace, string $method, string $url, array $options = []): array
    {
        $options['headers'][$this->clientHeader] ??= $trace->getTraceId();

        return $options;
    }
}
