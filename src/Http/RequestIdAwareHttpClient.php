<?php

declare(strict_types=1);

namespace DR\SymfonyRequestId\Http;

use DR\SymfonyRequestId\RequestIdStorage;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;
use Symfony\Contracts\Service\ResetInterface;

#[AsDecorator('http_client')]
class RequestIdAwareHttpClient implements HttpClientInterface, ResetInterface, LoggerAwareInterface
{
    public function __construct(
        private HttpClientInterface $client,
        private readonly RequestIdStorage $storage,
        private readonly string $requestIdHeader
    ) {
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $options['headers'][$this->requestIdHeader] = $this->storage->getRequestId();

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
