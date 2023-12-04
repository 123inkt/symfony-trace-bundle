<?php

declare(strict_types=1);

namespace DR\SymfonyRequestId\Tests\Unit\Http;

use DR\SymfonyRequestId\Http\RequestIdAwareHttpClient;
use DR\SymfonyRequestId\RequestIdStorageInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\ScopingHttpClient;

#[CoversClass(RequestIdAwareHttpClient::class)]
class RequestIdAwareHttpClientTest extends TestCase
{
    private ScopingHttpClient&MockObject $client;
    private RequestIdStorageInterface&MockObject $storage;
    private RequestIdAwareHttpClient $requestIdAwareHttpClient;

    protected function setUp(): void
    {
        $this->client                   = $this->createMock(ScopingHttpClient::class);
        $this->storage                  = $this->createMock(RequestIdStorageInterface::class);
        $this->requestIdAwareHttpClient = new RequestIdAwareHttpClient($this->client, $this->storage, 'X-Request-Id');
    }

    public function testRequest(): void
    {
        $this->storage->expects(self::once())->method('getRequestId')->willReturn('12345');
        $this->client->expects(self::once())->method('request')->with('GET', 'http://localhost', [
            'headers' => [
                'X-Request-Id' => '12345',
            ],
        ]);

        $this->requestIdAwareHttpClient->request('GET', 'http://localhost');
    }

    public function testStream(): void
    {
        $this->client->expects(self::once())->method('stream')->with([]);

        $this->requestIdAwareHttpClient->stream([]);
    }

    public function testReset(): void
    {
        $this->client->expects(self::once())->method('reset');

        $this->requestIdAwareHttpClient->reset();
    }

    public function testSetLogger(): void
    {
        $this->client->expects(self::once())->method('setLogger');

        $this->requestIdAwareHttpClient->setLogger($this->createMock(LoggerInterface::class));
    }

    public function testWithOptions(): void
    {
        $this->client->expects(self::once())->method('withOptions')->willReturn($this->client);

        $this->requestIdAwareHttpClient->withOptions([]);
    }
}
