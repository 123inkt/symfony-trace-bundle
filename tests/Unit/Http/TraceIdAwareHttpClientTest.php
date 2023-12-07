<?php

declare(strict_types=1);

namespace DR\SymfonyRequestId\Tests\Unit\Http;

use DR\SymfonyRequestId\Http\TraceIdAwareHttpClient;
use DR\SymfonyRequestId\IdStorageInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\ScopingHttpClient;

#[CoversClass(TraceIdAwareHttpClient::class)]
class TraceIdAwareHttpClientTest extends TestCase
{
    private ScopingHttpClient&MockObject $client;
    private IdStorageInterface&MockObject $storage;
    private TraceIdAwareHttpClient $traceIdAwareHttpClient;

    protected function setUp(): void
    {
        $this->client                 = $this->createMock(ScopingHttpClient::class);
        $this->storage                = $this->createMock(IdStorageInterface::class);
        $this->traceIdAwareHttpClient = new TraceIdAwareHttpClient($this->client, $this->storage, 'X-Request-Id');
    }

    public function testRequest(): void
    {
        $this->storage->expects(self::once())->method('getTraceId')->willReturn('12345');
        $this->client->expects(self::once())->method('request')->with('GET', 'http://localhost', [
            'headers' => [
                'X-Request-Id' => '12345',
            ],
        ]);

        $this->traceIdAwareHttpClient->request('GET', 'http://localhost');
    }

    public function testStream(): void
    {
        $this->client->expects(self::once())->method('stream')->with([]);

        $this->traceIdAwareHttpClient->stream([]);
    }

    public function testReset(): void
    {
        $this->client->expects(self::once())->method('reset');

        $this->traceIdAwareHttpClient->reset();
    }

    public function testSetLogger(): void
    {
        $this->client->expects(self::once())->method('setLogger');

        $this->traceIdAwareHttpClient->setLogger($this->createMock(LoggerInterface::class));
    }

    public function testWithOptions(): void
    {
        $this->client->expects(self::once())->method('withOptions')->willReturn($this->client);

        $this->traceIdAwareHttpClient->withOptions([]);
    }
}
