<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Tests\Unit\Http;

use DR\SymfonyTraceBundle\Http\TraceAwareHttpClient;
use DR\SymfonyTraceBundle\Service\TraceContextService;
use DR\SymfonyTraceBundle\Service\TraceServiceInterface;
use DR\SymfonyTraceBundle\TraceId;
use DR\SymfonyTraceBundle\TraceStorageInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\ScopingHttpClient;

#[CoversClass(TraceAwareHttpClient::class)]
class TraceAwareHttpClientTest extends TestCase
{
    private ScopingHttpClient&MockObject $client;
    private TraceStorageInterface&MockObject $storage;
    private TraceServiceInterface&MockObject $service;
    private TraceAwareHttpClient $traceClient;

    protected function setUp(): void
    {
        $this->client      = $this->createMock(ScopingHttpClient::class);
        $this->storage     = $this->createMock(TraceStorageInterface::class);
        $this->service     = $this->createMock(TraceServiceInterface::class);
        $this->traceClient = new TraceAwareHttpClient($this->client, $this->storage, $this->service);
    }

    public function testRequest(): void
    {
        $trace = new TraceId();
        $trace->setTraceId('12345');

        $this->storage->expects(static::once())->method('getTrace')->willReturn($trace);
        $this->service->expects(static::once())->method('handleClientRequest')
            ->with($trace, 'GET', 'http://localhost', [])->willReturn(['headers' => ['X-Trace-Id' => '12345']]);
        $this->client->expects(self::once())->method('request')
            ->with('GET', 'http://localhost', ['headers' => ['X-Trace-Id' => '12345']]);

        $this->traceClient->request('GET', 'http://localhost');
    }

    public function testStream(): void
    {
        $this->client->expects(self::once())->method('stream')->with([]);

        $this->traceClient->stream([]);
    }

    public function testReset(): void
    {
        $this->client->expects(self::once())->method('reset');

        $this->traceClient->reset();
    }

    public function testSetLogger(): void
    {
        $this->client->expects(self::once())->method('setLogger');

        $this->traceClient->setLogger($this->createMock(LoggerInterface::class));
    }

    public function testWithOptions(): void
    {
        $this->client->expects(self::once())->method('withOptions')->willReturn($this->client);

        $this->traceClient->withOptions([]);
    }
}
