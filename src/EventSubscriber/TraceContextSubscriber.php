<?php

declare(strict_types=1);

namespace DR\SymfonyRequestId\EventSubscriber;

use DR\SymfonyRequestId\Generator\TraceId\TraceIdGeneratorInterface;
use DR\SymfonyRequestId\Generator\TraceContext\TraceContextIdGenerator;
use DR\SymfonyRequestId\TraceStorageInterface;
use DR\SymfonyRequestId\Service\TraceContextService;
use DR\SymfonyRequestId\TraceContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Listens for requests and responses and sets up the trace ID on each if available.
 * @internal
 */
final class TraceContextSubscriber implements EventSubscriberInterface
{
    /**
     * @param bool                    $trustRequest            Trust the value from the request? Or generate?
     * @param TraceContextService     $traceContextService     Service class to validate and parse the tracecontext headers
     * @param TraceStorageInterface   $traceStorage            The trace ID storage, used to store the ID from the request or a newly generated ID.
     * @param TraceContextIdGenerator $traceContextIdGenerator Used to generate tracecontext IDs
     */
    public function __construct(
        private readonly bool                    $trustRequest,
        private readonly TraceContextService     $traceContextService,
        private readonly TraceStorageInterface   $traceStorage,
        private readonly TraceContextIdGenerator $traceContextIdGenerator
    ) {
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST  => ['onRequest', 100],
            KernelEvents::RESPONSE => ['onResponse', -99],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if ($event->isMainRequest() === false) {
            return;
        }

        $request = $event->getRequest();
        if ($this->trustRequest === false) {
            return;
        }

        // If the request contains a valid traceparent header, parse it and the tracestate and store it.
        $traceParent = $request->headers->get(TraceContextService::HEADER_TRACEPARENT);
        if ($traceParent !== null && $this->traceContextService->validateTraceParent($traceParent)) {
            $traceState   = $request->headers->get(TraceContextService::HEADER_TRACESTATE, '');
            $traceContext = $this->traceContextService->parseTraceContext($traceParent, $traceState);
        } else {
            // generate a new tracecontext, discard a potential tracestate header
            $traceContext = new TraceContext();
            $traceContext->setTraceId($this->traceContextIdGenerator->generateTraceId());
        }

        $traceContext->setTransactionId($this->traceContextIdGenerator->generateTransactionId());
        $this->traceStorage->setTrace($traceContext);
    }

    public function onResponse(ResponseEvent $event): void
    {
        if ($event->isMainRequest() === false) {
            return;
        }

        $traceContext = $this->traceStorage->getTrace();
        if ($traceContext instanceof TraceContext === false) {
            return;
        }

        $headers      = $event->getResponse()->headers;
        $headers->set(TraceContextService::HEADER_TRACEPARENT, $this->traceContextService->renderTraceParent($traceContext));
        $headers->set(TraceContextService::HEADER_TRACESTATE, $this->traceContextService->renderTraceState($traceContext));
    }
}
