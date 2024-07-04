<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Tests\Unit\EventSubscriber;

use DR\SymfonyTraceBundle\EventSubscriber\TraceSubscriber;
use DR\SymfonyTraceBundle\Service\TraceServiceInterface;
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

#[CoversClass(TraceSubscriber::class)]
class TraceSubscriberTest extends TestCase
{
    private TraceServiceInterface&MockObject $service;
    private TraceStorageInterface&MockOBject $storage;
    private TraceSubscriber $listener;
    private EventDispatcher $dispatcher;
    private Request $request;
    private Response $response;
    private HttpKernelInterface&MockObject $kernel;

    protected function setUp(): void
    {
        $this->service   = $this->createMock(TraceServiceInterface::class);
        $this->storage   = $this->createMock(TraceStorageInterface::class);
        $this->listener  = new TraceSubscriber(true, null, true, null, $this->service, $this->storage);

        $this->dispatcher = new EventDispatcher();
        $this->dispatcher->addSubscriber($this->listener);
        $this->request  = Request::create('/');
        $this->response = new Response('Hello, World');
        $this->kernel   = $this->createMock(HttpKernelInterface::class);
    }

    public function testNonMasterRequestsDoNothingOnRequest(): void
    {
        $event = new RequestEvent($this->kernel, $this->request, HttpKernelInterface::SUB_REQUEST);
        $this->storage->expects(static::never())->method('setTrace');

        $this->dispatcher->dispatch($event, KernelEvents::REQUEST);
    }

    /**
     * When a request is received with the traceId in the header, this same value is used as the current traceId.
     * A new transactionId is always generated.
     */
    public function testListenerSetsTheTraceToStorageWhenFoundInRequestHeaders(): void
    {
        $trace = new TraceContext();
        $this->service->expects(static::once())->method('supports')->willReturn(true);
        $this->service->expects(static::never())->method('createNewTrace');
        $this->service->expects(static::once())->method('getRequestTrace')->willReturn($trace);
        $this->storage->expects(static::once())->method('setTrace')->with($trace);

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
        $this->dispatcher->addSubscriber(new TraceSubscriber(false, null, true, null, $this->service, $this->storage));

        $trace = new TraceContext();
        $this->service->expects(static::never())->method('supports');
        $this->service->expects(static::once())->method('createNewTrace')->willReturn($trace);
        $this->service->expects(static::never())->method('getRequestTrace');
        $this->storage->expects(static::once())->method('setTrace')->with($trace);

        $event = new RequestEvent($this->kernel, $this->request, HttpKernelInterface::MAIN_REQUEST);
        $this->dispatcher->dispatch($event, KernelEvents::REQUEST);
    }

    /**
     * When a request is received without the traceId in the header, and is not found in the IdStorage a new value is generated.
     * This value is used as the traceId and a new transactionId is generated.
     */
    public function testListenerGenerateNewIdAndSetsItOnRequestAndStorageWhenNoIdIsFound(): void
    {
        $trace = new TraceContext();
        $this->service->expects(static::once())->method('supports')->willReturn(false);
        $this->service->expects(static::once())->method('createNewTrace')->willReturn($trace);
        $this->service->expects(static::never())->method('getRequestTrace');
        $this->storage->expects(static::once())->method('setTrace')->with($trace);

        $event = new RequestEvent($this->kernel, $this->request, HttpKernelInterface::MAIN_REQUEST);
        $this->dispatcher->dispatch($event, KernelEvents::REQUEST);
    }

    public function testListenerDoesNothingWhenStorageIdIsFound(): void
    {
        $this->service->expects(static::once())->method('supports')->willReturn(false);
        $this->service->expects(static::never())->method('createNewTrace');
        $this->storage->expects(static::once())->method('getTraceId')->willReturn("abc123");

        $event = new RequestEvent($this->kernel, $this->request, HttpKernelInterface::MAIN_REQUEST);
        $this->dispatcher->dispatch($event, KernelEvents::REQUEST);
    }

    public function testListenerDoesNothingToResponseWithoutMasterRequest(): void
    {
        $this->storage->expects(static::never())->method('getTrace');
        $this->service->expects(static::never())->method('handleResponse');

        $this->dispatcher->dispatch(
            new ResponseEvent($this->kernel, $this->request, HttpKernelInterface::SUB_REQUEST, $this->response),
            KernelEvents::RESPONSE
        );
    }

    public function testRequestSetsIdOnResponse(): void
    {
        $trace = new TraceContext();
        $this->storage->expects(static::once())->method('getTrace')->willReturn($trace);
        $this->service->expects(static::once())->method('handleResponse')->with($this->response, $trace);

        $this->dispatcher->dispatch(
            new ResponseEvent($this->kernel, $this->request, HttpKernelInterface::MAIN_REQUEST, $this->response),
            KernelEvents::RESPONSE
        );
    }

    public function testListenerDoesNothingToResponseWithoutMasterRequestWhenSendResponseHeaderIsFalse(): void
    {
        $this->dispatcher->removeSubscriber($this->listener);
        $this->dispatcher->addSubscriber(new TraceSubscriber(false, null, false, null, $this->service, $this->storage));

        $this->storage->expects(static::never())->method('getTrace');
        $this->service->expects(static::never())->method('handleResponse');

        $this->dispatcher->dispatch(
            new ResponseEvent($this->kernel, $this->request, HttpKernelInterface::MAIN_REQUEST, $this->response),
            KernelEvents::RESPONSE
        );
    }

    public function testListenerSetsTheTraceToStorageWhenFoundInTrustedRequestHeaders(): void
    {
        $listener   = new TraceSubscriber(true, '127.0.0.1', true, '127.0.0.1', $this->service, $this->storage);
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber($listener);

        $this->service->expects(static::once())->method('getRequestTrace')->with($this->request);
        $this->service->expects(static::once())->method('supports')->with($this->request)->willReturn(true);
        $this->storage->expects(static::once())->method('setTrace');

        $dispatcher->dispatch(
            new RequestEvent($this->kernel, $this->request, HttpKernelInterface::MAIN_REQUEST),
            KernelEvents::REQUEST
        );
    }

    public function testListenerIgnoresRequestHeadersOnNonTrustedRequest(): void
    {
        $listener   = new TraceSubscriber(true, '127.0.0.2', true, '127.0.0.2', $this->service, $this->storage);
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber($listener);

        $this->service->expects(static::never())->method('supports');
        $this->service->expects(static::never())->method('getRequestTrace');

        $dispatcher->dispatch(
            new RequestEvent($this->kernel, $this->request, HttpKernelInterface::MAIN_REQUEST),
            KernelEvents::REQUEST
        );
    }

    public function testRequestSetsIdOnResponseOnTrustedIp(): void
    {
        $listener   = new TraceSubscriber(true, '127.0.0.1', true, '127.0.0.1', $this->service, $this->storage);
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber($listener);

        $trace = new TraceContext();
        $this->storage->expects(static::once())->method('getTrace')->willReturn($trace);
        $this->service->expects(static::once())->method('handleResponse')->with($this->response, $trace);

        $dispatcher->dispatch(
            new ResponseEvent($this->kernel, $this->request, HttpKernelInterface::MAIN_REQUEST, $this->response),
            KernelEvents::RESPONSE
        );
    }

    public function testRequestDoesNotSetIdOnResponseOnNonTrustedIp(): void
    {
        $listener   = new TraceSubscriber(true, '127.0.0.2', true, '127.0.0.2', $this->service, $this->storage);
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber($listener);

        $this->storage->expects(static::never())->method('getTrace');
        $this->service->expects(static::never())->method('handleResponse');

        $dispatcher->dispatch(
            new ResponseEvent($this->kernel, $this->request, HttpKernelInterface::MAIN_REQUEST, $this->response),
            KernelEvents::RESPONSE
        );
    }
}
