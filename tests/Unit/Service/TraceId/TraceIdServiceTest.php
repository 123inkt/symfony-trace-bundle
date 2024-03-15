<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Tests\Unit\Service\TraceId;

use DR\SymfonyTraceBundle\Generator\TraceIdGeneratorInterface;
use DR\SymfonyTraceBundle\Service\TraceId\TraceIdService;
use DR\SymfonyTraceBundle\TraceContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(TraceIdService::class)]
class TraceIdServiceTest extends TestCase
{
    private const REQUEST_HEADER  = 'X-Trace-Request';
    private const RESPONSE_HEADER = 'X-Trace-Response';
    private const CLIENT_HEADER   = 'X-Trace-Id';

    private TraceIdGeneratorInterface&MockObject $generator;
    private TraceIdService $service;

    protected function setUp(): void
    {
        $this->generator = $this->createMock(TraceIdGeneratorInterface::class);
        $this->service   = new TraceIdService(self::REQUEST_HEADER, self::RESPONSE_HEADER, self::CLIENT_HEADER, $this->generator);
    }

    public function testSupports(): void
    {
        $request = new Request();
        $request->headers->set(self::REQUEST_HEADER, 'abc');

        static::assertTrue($this->service->supports($request));
    }

    public function testSupportsNoHeader(): void
    {
        $request = new Request();
        static::assertFalse($this->service->supports($request));
    }

    public function testCreateNewTrace(): void
    {
        $this->generator->expects(static::once())->method('generateTraceId')->willReturn('abc');
        $this->generator->expects(static::once())->method('generateTransactionId')->willReturn('123');

        $trace = $this->service->createNewTrace();
        static::assertSame('abc', $trace->getTraceId());
        static::assertSame('123', $trace->getTransactionId());
    }

    public function testCreateNewFrom(): void
    {
        $this->generator->expects(static::never())->method('generateTraceId');
        $this->generator->expects(static::once())->method('generateTransactionId')->willReturn('123');

        $trace = $this->service->createTraceFrom('test-trace-id');
        static::assertSame('test-trace-id', $trace->getTraceId());
        static::assertSame('123', $trace->getTransactionId());
    }

    public function testGetRequestTrace(): void
    {
        $this->generator->expects(static::once())->method('generateTransactionId')->willReturn('123');

        $request = new Request();
        $request->headers->set(self::REQUEST_HEADER, 'abc');

        $trace = $this->service->getRequestTrace($request);
        static::assertSame('abc', $trace->getTraceId());
        static::assertSame('123', $trace->getTransactionId());
    }

    public function testHandleResponse(): void
    {
        $trace = new TraceContext();
        $trace->setTraceId('abc');
        $trace->setTransactionId('123');

        $response = new Response();
        $this->service->handleResponse($response, $trace);
        static::assertSame('abc', $response->headers->get(self::RESPONSE_HEADER));
    }

    public function testHandleClientRequest(): void
    {
        $trace = new TraceContext();
        $trace->setTraceId('abc');
        $trace->setTransactionId('123');

        $options = $this->service->handleClientRequest($trace, 'GET', 'http://example.com');
        static::assertSame('abc', $options['headers'][self::CLIENT_HEADER]);
    }
}
