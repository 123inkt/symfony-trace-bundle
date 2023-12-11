<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Tests\Unit\EventSubscriber;

use DR\SymfonyTraceBundle\EventSubscriber\TraceContextSubscriber;
use DR\SymfonyTraceBundle\Generator\TraceContext\TraceContextIdGenerator;
use DR\SymfonyTraceBundle\Service\TraceContextService;
use DR\SymfonyTraceBundle\TraceContext;
use DR\SymfonyTraceBundle\TraceStorageInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

#[CoversClass(TraceContextSubscriber::class)]
class TraceContextSubscriberTest extends TestCase
{
    private TraceStorageInterface&MockOBject $storage;
    private TraceContextIdGenerator&MockObject $generator;
    private TraceContextService&MockObject $service;
    private TraceContextSubscriber $listener;
    private EventDispatcher $dispatcher;
    private Request $request;
    private Response $response;
    private HttpKernelInterface&MockObject $kernel;

    protected function setUp(): void
    {
        $this->storage   = $this->createMock(TraceStorageInterface::class);
        $this->service   = $this->createMock(TraceContextService::class);
        $this->generator = $this->createMock(TraceContextIdGenerator::class);
        $this->listener  = new TraceContextSubscriber(true, $this->service, $this->storage, $this->generator);

        $this->dispatcher = new EventDispatcher();
        $this->dispatcher->addSubscriber($this->listener);
        $this->request  = Request::create('/');
        $this->response = new Response('Hello, World');
        $this->kernel   = $this->createMock(HttpKernelInterface::class);
    }

    public function testNonMasterRequestsDoNothingOnRequest(): void
    {
        $event = new RequestEvent($this->kernel, $this->request, HttpKernelInterface::SUB_REQUEST);
        $this->storage->expects(self::never())->method('getTraceId');

        $this->dispatcher->dispatch($event, KernelEvents::REQUEST);
    }

    /**
     * When a request is received with the traceId in the header, this same value is used as the current traceId.
     * A new transactionId is always generated.
     */
    public function testListenerSetsTheTraceIdToStorageWhenFoundInRequestHeaders(): void
    {
        $traceContext = new TraceContext();
        $traceContext->setTraceId('testId');
        $traceContext->setTransactionId('transactionId');

        $this->service->expects(self::once())->method('validateTraceParent')->willReturn(true);
        $this->service->expects(self::once())->method('parseTraceContext')->willReturn($traceContext);
        $this->request->headers->set(TraceContextService::HEADER_TRACEPARENT, 'testId');
        $this->generator->expects(self::once())->method('generateTransactionId')->willReturn('transactionId');
        $this->storage->expects(self::never())->method('getTraceId');
        $this->storage->expects(self::once())->method('setTrace')->with($traceContext);

        $event = new RequestEvent($this->kernel, $this->request, HttpKernelInterface::MAIN_REQUEST);
        $this->dispatcher->dispatch($event, KernelEvents::REQUEST);
    }

    /**
     * When a request is received without the traceId in the header, and is not found in the IdStorage a new value is generated.
     * This value is used as the traceId and a new transactionId is generated.
     */
    public function testListenerGenerateNewIdAndSetsItOnRequestAndStorageWhenNoIdIsFound(): void
    {
        $traceContext = new TraceContext();
        $traceContext->setTraceId('def234');
        $traceContext->setTransactionId('transactionId');

        $this->generator->expects(self::once())->method('generateTraceId')->willReturn('def234');
        $this->generator->expects(self::once())->method('generateTransactionId')->willReturn('transactionId');
        $this->storage->expects(self::once())->method('setTrace')->with($traceContext);
        $event = new RequestEvent($this->kernel, $this->request, HttpKernelInterface::MAIN_REQUEST);

        $this->dispatcher->dispatch($event, KernelEvents::REQUEST);
    }

    /**
     * When a request is received with the traceId in the header. But trustHeader config is false, so we don't use this.
     * A new traceId and a new transactionId is generated.
     */
    public function testListenerIgnoresIncomingRequestHeadersWhenTrustRequestIsFalse(): void
    {
        $this->dispatcher->removeSubscriber($this->listener);
        $this->listener = new TraceContextSubscriber(false, $this->service, $this->storage, $this->generator);
        $this->generator->expects(self::never())->method('generateTraceId');
        $this->generator->expects(self::never())->method('generateTransactionId');
        $this->storage->expects(self::never())->method('setTrace');

        $this->request->headers->set(TraceContextService::HEADER_TRACEPARENT, 'abc123');
        $event = new RequestEvent($this->kernel, $this->request, HttpKernelInterface::MAIN_REQUEST);

        $this->dispatcher->dispatch($event, KernelEvents::REQUEST);
    }

    public function testListenerDoesNothingToResponseWithoutMasterRequest(): void
    {
        $this->storage->expects(self::never())->method('setTrace');
        $this->dispatcher->dispatch(
            new ResponseEvent($this->kernel, $this->request, HttpKernelInterface::SUB_REQUEST, $this->response),
            KernelEvents::RESPONSE
        );

        static::assertFalse($this->response->headers->has(TraceContextService::HEADER_TRACEPARENT));
    }

    public function testRequestWithIdInStorageSetsIdOnResponse(): void
    {
        $traceContext = new TraceContext();
        $traceContext->setTraceId('def234');
        $traceContext->setTransactionId('transactionId');

        $this->storage->expects(self::once())->method('getTrace')->willReturn($traceContext);
        $this->service->expects(self::once())->method('renderTraceParent')->willReturn('00-def234-transactionId-00');
        $this->dispatcher->dispatch(
            new ResponseEvent($this->kernel, $this->request, HttpKernelInterface::MAIN_REQUEST, $this->response),
            KernelEvents::RESPONSE
        );

        static::assertEquals('00-def234-transactionId-00', $this->response->headers->get(TraceContextService::HEADER_TRACEPARENT));
    }
}
