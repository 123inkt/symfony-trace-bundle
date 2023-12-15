<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\EventSubscriber;

use DR\SymfonyTraceBundle\Service\TraceServiceInterface;
use DR\SymfonyTraceBundle\TraceStorageInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Listens for requests and responses and sets up the trace ID on each if available.
 * @internal
 */
final class TraceSubscriber implements EventSubscriberInterface
{
    /**
     * @param bool                      $trustRequest Trust the value from the request? Or generate?
     * @param TraceStorageInterface     $traceStorage The trace ID storage, used to store the ID from the request or a newly generated ID.
     */
    public function __construct(
        private readonly bool                  $trustRequest,
        private readonly bool                  $sendResponseHeader,
        private readonly TraceServiceInterface $traceService,
        private readonly TraceStorageInterface $traceStorage,
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

        // If we trust the request, check if the traceService supports it and use the request data
        if ($this->trustRequest && $this->traceService->supports($request)) {
            $this->traceStorage->setTrace($this->traceService->getRequestTrace($request));

            return;
        }

        $this->traceStorage->setTrace($this->traceService->createNewTrace());
    }

    public function onResponse(ResponseEvent $event): void
    {
        if ($event->isMainRequest() === false) {
            return;
        }

        if ($this->sendResponseHeader) {
            $this->traceService->handleResponse($event->getResponse(), $this->traceStorage->getTrace());
        }
    }
}
