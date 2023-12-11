<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Http;

use DR\SymfonyTraceBundle\Service\TraceServiceInterface;
use DR\SymfonyTraceBundle\TraceStorageInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;
use Symfony\Contracts\Service\ResetInterface;

class TraceAwareHttpClient implements HttpClientInterface, ResetInterface, LoggerAwareInterface
{
    public function __construct(
        private HttpClientInterface            $client,
        private readonly TraceStorageInterface $storage,
        private readonly TraceServiceInterface $service,
    ) {
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $options = $this->service->handleClientRequest($this->storage->getTrace(), $method, $url, $options);

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
