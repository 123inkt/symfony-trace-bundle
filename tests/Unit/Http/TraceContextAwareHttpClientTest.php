<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Tests\Unit\Http;

use DR\SymfonyTraceBundle\Http\TraceContextAwareHttpClient;
use DR\SymfonyTraceBundle\Service\TraceContextService;
use DR\SymfonyTraceBundle\TraceContext;
use DR\SymfonyTraceBundle\TraceStorageInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\ScopingHttpClient;

#[CoversClass(TraceContextAwareHttpClient::class)]
class TraceContextAwareHttpClientTest extends TestCase
{
    private ScopingHttpClient&MockObject $client;
    private TraceStorageInterface&MockObject $storage;
    private TraceContextService&MockObject $service;
    private TraceContextAwareHttpClient $traceAwareHttpClient;

    protected function setUp(): void
    {
        $this->client               = $this->createMock(ScopingHttpClient::class);
        $this->storage              = $this->createMock(TraceStorageInterface::class);
        $this->service              = $this->createMock(TraceContextService::class);
        $this->traceAwareHttpClient = new TraceContextAwareHttpClient($this->client, $this->storage, $this->service);
    }

    public function testRequest(): void
    {
        $this->storage->method('getTrace')->willReturn(new TraceContext());
        $this->service->method('renderTraceParent')->willReturn('00-123-ABC-00');
        $this->client->expects(self::once())->method('request')->with('GET', 'http://localhost', [
            'headers' => ['traceparent' => '00-123-ABC-00'],
        ]);

        $this->traceAwareHttpClient->request('GET', 'http://localhost');
    }

    public function testRequestTraceState(): void
    {
        $this->storage->method('getTrace')->willReturn(new TraceContext());
        $this->service->method('renderTraceParent')->willReturn('00-123-ABC-00');
        $this->service->method('renderTraceState')->willReturn('dr=1');
        $this->client->expects(self::once())->method('request')->with('GET', 'http://localhost', [
            'headers' => ['traceparent' => '00-123-ABC-00', 'tracestate' => 'dr=1'],
        ]);

        $this->traceAwareHttpClient->request('GET', 'http://localhost');
    }

    public function testStream(): void
    {
        $this->client->expects(self::once())->method('stream')->with([]);

        $this->traceAwareHttpClient->stream([]);
    }

    public function testReset(): void
    {
        $this->client->expects(self::once())->method('reset');

        $this->traceAwareHttpClient->reset();
    }

    public function testSetLogger(): void
    {
        $this->client->expects(self::once())->method('setLogger');

        $this->traceAwareHttpClient->setLogger($this->createMock(LoggerInterface::class));
    }

    public function testWithOptions(): void
    {
        $this->client->expects(self::once())->method('withOptions')->willReturn($this->client);

        $this->traceAwareHttpClient->withOptions([]);
    }
}
