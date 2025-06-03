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

/**
 * @phpstan-type HttpClientOptions array{
 *     auth_basic?: string[]|string,
 *     auth_bearer?: string,
 *     query?: string[],
 *     headers?: string[]|string[][],
 *     body?: mixed[]|string|resource|\Traversable|\Closure|object,
 *     json?: mixed,
 *     user_data?: mixed,
 *     max_redirects?: int,
 *     http_version?: string,
 *     base_uri?: string,
 *     buffer?: bool|resource|\Closure,
 *     on_progress?: callable(int $dlNow, int $dlSize, mixed[] $info): void,
 *     resolve?: string[],
 *     proxy?: string,
 *     no_proxy?: string,
 *     timeout?: int|float,
 *     max_duration?: int|float,
 *     bindto?: string,
 *     verify_peer?: bool,
 *     verify_host?: bool,
 *     cafile?: string,
 *     capath?: string,
 *     local_cert?: string,
 *     local_pk?: string,
 *     passphrase?: string,
 *     ciphers?: string,
 *     peer_fingerprint?: string,
 *     capture_peer_cert_chain?: bool,
 *     extra?: mixed[]
 * }
 * @internal
 */
class TraceAwareHttpClient implements HttpClientInterface, ResetInterface, LoggerAwareInterface
{
    public function __construct(
        private HttpClientInterface            $client,
        private readonly TraceStorageInterface $storage,
        private readonly TraceServiceInterface $service,
    ) {
    }

    /**
     * @param HttpClientOptions $options
     */
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

    /**
     * @param HttpClientOptions $options
     */
    public function withOptions(array $options): static
    {
        $this->client = $this->client->withOptions($options);

        return $this;
    }
}
