<?php

declare(strict_types=1);

namespace DR\SymfonyRequestId\Http;

use DR\SymfonyRequestId\Service\TraceContextService;
use DR\SymfonyRequestId\TraceContext;
use DR\SymfonyRequestId\TraceId;
use DR\SymfonyRequestId\TraceStorageInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;
use Symfony\Contracts\Service\ResetInterface;

class TraceContextAwareHttpClient implements HttpClientInterface, ResetInterface, LoggerAwareInterface
{
    public function __construct(
        private HttpClientInterface            $client,
        private readonly TraceStorageInterface $storage,
        private readonly TraceContextService   $traceContextService
    ) {
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        if ($this->storage->getTrace() instanceof TraceContext) {
            $traceParent = $this->traceContextService->renderTraceParent($this->storage->getTrace());
            $options['headers'][TraceContextService::HEADER_TRACEPARENT] = $traceParent;

            $traceState = $this->traceContextService->renderTraceState($this->storage->getTrace());
            if ($traceState !== '') {
                $options['headers'][TraceContextService::HEADER_TRACESTATE] = $traceState;
            }
        }

        return $this->client->request($method, $url, $options);
    }

    public function stream(iterable|ResponseInterface $responses, ?float $timeout = null): ResponseStreamInterface
    {
        return $this->client->stream($responses, $timeout);
    }

    public function reset(): void
    {
        if ($this->client instanceof ResetInterface) {
            $this->client->reset();
        }
    }

    public function setLogger(LoggerInterface $logger): void
    {
        if ($this->client instanceof LoggerAwareInterface) {
            $this->client->setLogger($logger);
        }
    }

    public function withOptions(array $options): static
    {
        $this->client = $this->client->withOptions($options);

        return $this;
    }
}
