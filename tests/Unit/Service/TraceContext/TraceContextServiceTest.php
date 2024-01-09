<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Tests\Unit\Service\TraceContext;

use DR\SymfonyTraceBundle\Generator\TraceContext\TraceContextIdGenerator;
use DR\SymfonyTraceBundle\Service\TraceContext\TraceContextService;
use DR\SymfonyTraceBundle\TraceContext;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(TraceContextService::class)]
class TraceContextServiceTest extends TestCase
{
    private TraceContextIdGenerator&MockObject $generator;
    private TraceContextService $service;

    protected function setUp(): void
    {
        $this->generator = $this->createMock(TraceContextIdGenerator::class);
        $this->service = new TraceContextService($this->generator);
    }

    #[DataProvider('provideTraceParent')]
    public function testSupports(string $value, bool $expectedSuccess): void
    {
        $request = new Request();
        $request->headers->set(TraceContextService::HEADER_TRACEPARENT, $value);

        static::assertSame($expectedSuccess, $this->service->supports($request));
    }

    public function testSupportsNoHeader(): void
    {
        $request = new Request();
        static::assertFalse($this->service->supports($request));
    }

    public function testCreateNewTrace(): void
    {
        $this->generator->expects(static::once())->method('generateTraceId')->willReturn('0af7651916cd43dd8448eb211c80319c');
        $this->generator->expects(static::once())->method('generateTransactionId')->willReturn('b7ad6b7169203331');

        $trace = $this->service->createNewTrace();
        static::assertSame('00', $trace->getVersion());
        static::assertSame('0af7651916cd43dd8448eb211c80319c', $trace->getTraceId());
        static::assertSame('b7ad6b7169203331', $trace->getTransactionId());
        static::assertSame('00', $trace->getFlags());
    }

    public function testGetRequestTrace(): void
    {
        $request = new Request();
        $request->headers->set(TraceContextService::HEADER_TRACEPARENT, '00-0af7651916cd43dd8448eb211c80319c-b7ad6b7169203331-00');
        $request->headers->set(TraceContextService::HEADER_TRACESTATE, 'foo=bar,bar=baz');

        $this->generator->expects(static::once())->method('generateTransactionId')->willReturn('00f067aa0ba902b7');
        $trace = $this->service->getRequestTrace($request);
        static::assertSame('00', $trace->getVersion());
        static::assertSame('0af7651916cd43dd8448eb211c80319c', $trace->getTraceId());
        static::assertSame('b7ad6b7169203331', $trace->getParentTransactionId());
        static::assertSame('00f067aa0ba902b7', $trace->getTransactionId());
        static::assertSame('00', $trace->getFlags());
        static::assertSame(['foo' => 'bar', 'bar' => 'baz'], $trace->getTraceState());
    }

    public function testHandleResponse(): void
    {
        $trace = new TraceContext();
        $trace->setTraceId('0af7651916cd43dd8448eb211c80319c');
        $trace->setTransactionId('b7ad6b7169203331');

        $response = new Response();
        $this->service->handleResponse($response, $trace);
        static::assertSame(
            '00-0af7651916cd43dd8448eb211c80319c-b7ad6b7169203331-00',
            $response->headers->get(TraceContextService::HEADER_TRACERESPONSE)
        );
    }

    public function testHandleClientRequest(): void
    {
        $trace = new TraceContext();
        $trace->setTraceId('0af7651916cd43dd8448eb211c80319c');
        $trace->setTransactionId('b7ad6b7169203331');

        static::assertSame(
            ['headers' => ['traceparent' => '00-0af7651916cd43dd8448eb211c80319c-b7ad6b7169203331-00']],
            $this->service->handleClientRequest($trace, 'GET', 'http://localhost')
        );
    }

    public function testHandleClientRequestTraceState(): void
    {
        $trace = new TraceContext();
        $trace->setTraceId('0af7651916cd43dd8448eb211c80319c');
        $trace->setTransactionId('b7ad6b7169203331');
        $trace->setTraceState(['foo' => 'bar', 'bar' => 'baz']);

        static::assertSame(
            ['headers' => [
                'traceparent' => '00-0af7651916cd43dd8448eb211c80319c-b7ad6b7169203331-00',
                'tracestate' => 'foo=bar,bar=baz']
            ],
            $this->service->handleClientRequest($trace, 'GET', 'http://localhost')
        );
    }

    public static function provideTraceParent(): Generator
    {
        yield ['00-0af7651916cd43dd8448eb211c80319c-b7ad6b7169203331-01', true];
        yield ['00-0af7651916cd43dd8448eb211c80319c-00f067aa0ba902b7-01', true];
        yield ['00-0af7651916cd43dd8448eb211c80319c-b9c7c989f97918e1-01', true];
        yield ['00-4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7-01', true];
        yield ['00-4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7-00', true];

        yield ['00-00f067aa0ba902b7-4bf92f3577b34da6a3ce929d0e0e4736-00', false];
        yield ['000-4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7-00', false];
        yield ['00-4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7-000', false];
    }
}
