<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\EventSubscriber;

use DR\SymfonyTraceBundle\Service\TraceServiceInterface;
use DR\SymfonyTraceBundle\TraceStorageInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Request;
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
     * @param bool $trustRequest                       Trust the value from the request? Or generate?
     * @param string[]|string|null $trustedIpsRequest  The IPs to trust the request from.
     * @param string[]|string|null $trustedIpsResponse The IPs to send the response header to.
     * @param TraceStorageInterface $traceStorage      The trace ID storage, used to store the ID from the request or a newly generated ID.
     */
    public function __construct(
        private readonly bool                  $trustRequest,
        private readonly array|string|null     $trustedIpsRequest,
        private readonly bool                  $sendResponseHeader,
        private readonly array|string|null     $trustedIpsResponse,
        private readonly TraceServiceInterface $traceService,
        private readonly TraceStorageInterface $traceStorage,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 100],
            KernelEvents::RESPONSE => ['onResponse', -99],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if ($event->isMainRequest() === false) {
            return;
        }

        $request      = $event->getRequest();
        $trustRequest = $this->trustRequest && $this->isTrustedRequest($request, $this->trustedIpsRequest);

        // If we trust the request, check if the traceService supports it and use the request data
        if ($trustRequest && $this->traceService->supports($request)) {
            $this->traceStorage->setTrace($this->traceService->getRequestTrace($request));

            return;
        }

        // If the trace ID is already set by another process, don't overwrite it
        if ($this->traceStorage->getTraceId() !== null) {
            return;
        }

        $this->traceStorage->setTrace($this->traceService->createNewTrace());
    }

    public function onResponse(ResponseEvent $event): void
    {
        if ($event->isMainRequest() === false) {
            return;
        }

        $request    = $event->getRequest();
        $sendHeader = $this->sendResponseHeader && $this->isTrustedRequest($request, $this->trustedIpsResponse);
        if ($sendHeader) {
            $this->traceService->handleResponse($event->getResponse(), $this->traceStorage->getTrace());
        }
    }

    /**
     * @param string[]|string|null $trustedIps
     */
    private function isTrustedRequest(Request $request, array|string|null $trustedIps): bool
    {
        if ($trustedIps === null) {
            return true;
        }

        if (is_string($trustedIps)) {
            // Support both comma and pipe as separators for trusted IPs
            $trustedIps = str_replace('|', ',', $trustedIps);
            $trustedIps = array_map('trim', explode(',', $trustedIps));
        }

        return IpUtils::checkIp((string)$request->getClientIp(), $trustedIps);
    }
}
