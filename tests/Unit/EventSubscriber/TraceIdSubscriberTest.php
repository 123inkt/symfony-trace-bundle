<?php

declare(strict_types=1);

namespace DR\SymfonyRequestId\Tests\Unit\EventSubscriber;

use DR\SymfonyRequestId\EventSubscriber\TraceIdSubscriber;
use DR\SymfonyRequestId\Generator\TraceId\TraceIdGeneratorInterface;
use DR\SymfonyRequestId\TraceStorageInterface;
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

#[CoversClass(TraceIdSubscriber::class)]
class TraceIdSubscriberTest extends TestCase
{
    private const REQUEST_HEADER  = 'Trace-Id';
    private const RESPONSE_HEADER = 'Response-Id';

    private TraceStorageInterface&MockOBject $idStorage;
    private TraceIdGeneratorInterface&MockObject $idGen;
    private TraceIdSubscriber $listener;
    private EventDispatcher $dispatcher;
    private Request $request;
    private Response $response;
    private HttpKernelInterface&MockObject $kernel;

    protected function setUp(): void
    {
        $this->idStorage  = $this->createMock(TraceStorageInterface::class);
        $this->idGen      = $this->createMock(TraceIdGeneratorInterface::class);
        $this->listener   = new TraceIdSubscriber(
            self::REQUEST_HEADER,
            self::RESPONSE_HEADER,
            true,
            $this->idStorage,
            $this->idGen
        );
        $this->dispatcher = new EventDispatcher();
        $this->dispatcher->addSubscriber($this->listener);
        $this->request  = Request::create('/');
        $this->response = new Response('Hello, World');
        $this->kernel   = $this->createMock(HttpKernelInterface::class);
    }

    public function testNonMasterRequestsDoNothingOnRequest(): void
    {
        $event = new RequestEvent(
            $this->kernel,
            $this->request,
            HttpKernelInterface::SUB_REQUEST
        );
        $this->idStorage->expects(self::never())
            ->method('getTraceId');

        $this->dispatcher->dispatch($event, KernelEvents::REQUEST);
    }

    /**
     * When a request is received with the traceId in the header, this same value is used as the current traceId.
     * A new transactionId is always generated.
     */
    public function testListenerSetsTheTraceIdToStorageWhenFoundInRequestHeaders(): void
    {
        $this->request->headers->set(self::REQUEST_HEADER, 'testId');
        $this->idGen->expects(self::once())->method('generate')->willReturn('transactionId');
        $this->idStorage->expects(self::never())
            ->method('getTraceId');
        $this->idStorage->expects(self::once())
            ->method('setTraceId')
            ->with('testId');
        $this->idStorage->expects(self::once())
            ->method('setTransactionId')
            ->with('transactionId');

        $event = new RequestEvent(
            $this->kernel,
            $this->request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->dispatcher->dispatch($event, KernelEvents::REQUEST);
    }

    /**
     * When a request is received without the traceId in the header, but is found in the IdStorage this value is used.
     * A new transactionId is always generated.
     */
    public function testListenerSetsTheIdOnRequestWhenItsFoundInStorage(): void
    {
        $this->idGen->expects(self::once())->method('generate')->willReturn('transactionId');
        $this->idStorage->expects(self::exactly(2))
            ->method('getTraceId')
            ->willReturn('abc123');
        $this->idStorage->expects(self::never())
            ->method('setTraceId');
        $this->idStorage->expects(self::once())
            ->method('setTransactionId')
            ->with('transactionId');

        $event = new RequestEvent(
            $this->kernel,
            $this->request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->dispatcher->dispatch($event, KernelEvents::REQUEST);

        static::assertEquals('abc123', $this->request->headers->get(self::REQUEST_HEADER));
    }

    /**
     * When a request is received without the traceId in the header, and is not found in the IdStorage a new value is generated.
     * This value is used as the traceId and a new transactionId is generated.
     */
    public function testListenerGenerateNewIdAndSetsItOnRequestAndStorageWhenNoIdIsFound(): void
    {
        $this->idGen->expects(self::exactly(2))
            ->method('generate')
            ->willReturn('transactionId', 'def234');
        $this->idStorage->expects(self::once())
            ->method('getTraceId')
            ->willReturn(null);
        $this->idStorage->expects(self::once())
            ->method('setTraceId')
            ->with('def234');
        $this->idStorage->expects(self::once())
            ->method('setTransactionId')
            ->with('transactionId');

        $event = new RequestEvent(
            $this->kernel,
            $this->request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->dispatcher->dispatch($event, KernelEvents::REQUEST);

        static::assertEquals('def234', $this->request->headers->get(self::REQUEST_HEADER));
    }

    /**
     * When a request is received with the traceId in the header. But trustHeader config is false, so we don't use this.
     * A new traceId and a new transactionId is generated.
     */
    public function testListenerIgnoresIncomingRequestHeadersWhenTrustRequestIsFalse(): void
    {
        $this->dispatcher->removeSubscriber($this->listener);
        $this->dispatcher->addSubscriber(
            new TraceIdSubscriber(
                self::REQUEST_HEADER,
                self::REQUEST_HEADER,
                false,
                $this->idStorage,
                $this->idGen
            )
        );
        $this->idGen->expects(self::exactly(2))
            ->method('generate')
            ->willReturn('transaction-id', 'def234');
        $this->idStorage->expects(self::once())
            ->method('getTraceId')
            ->willReturn(null);
        $this->idStorage->expects(self::once())
            ->method('setTransactionId')
            ->with('transaction-id');
        $this->idStorage->expects(self::once())
            ->method('setTraceId')
            ->with('def234');

        $this->request->headers->set(self::REQUEST_HEADER, 'abc123');
        $event = new RequestEvent(
            $this->kernel,
            $this->request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->dispatcher->dispatch($event, KernelEvents::REQUEST);

        static::assertEquals('def234', $this->request->headers->get(self::REQUEST_HEADER));
    }

    public function testListenerDoesNothingToResponseWithoutMasterRequest(): void
    {
        $this->idStorage->expects(self::never())
            ->method('getTraceId');

        $this->dispatcher->dispatch(
            new ResponseEvent(
                $this->kernel,
                $this->request,
                HttpKernelInterface::SUB_REQUEST,
                $this->response
            ),
            KernelEvents::RESPONSE
        );

        static::assertFalse($this->response->headers->has(self::REQUEST_HEADER));
    }

    public function testRequestWithoutIdInStorageDoesNotSetHeaderOnResponse(): void
    {
        $this->idStorage->expects(self::once())
            ->method('getTraceId')
            ->willReturn(null);

        $this->dispatcher->dispatch(
            new ResponseEvent(
                $this->kernel,
                $this->request,
                HttpKernelInterface::MAIN_REQUEST,
                $this->response
            ),
            KernelEvents::RESPONSE
        );

        static::assertFalse($this->response->headers->has(self::REQUEST_HEADER));
    }

    public function testRequestWithIdInStorageSetsIdOnResponse(): void
    {
        $this->idStorage->expects(self::exactly(2))
            ->method('getTraceId')
            ->willReturn('ghi345');

        $this->dispatcher->dispatch(
            new ResponseEvent(
                $this->kernel,
                $this->request,
                HttpKernelInterface::MAIN_REQUEST,
                $this->response
            ),
            KernelEvents::RESPONSE
        );

        static::assertEquals('ghi345', $this->response->headers->get(self::RESPONSE_HEADER));
    }
}
