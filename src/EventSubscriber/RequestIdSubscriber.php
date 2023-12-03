<?php

declare(strict_types=1);

namespace DR\SymfonyRequestId\EventSubscriber;

use DR\SymfonyRequestId\RequestIdGeneratorInterface;
use DR\SymfonyRequestId\RequestIdStorageInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Listens for requests and responses and sets up the request ID on each.
 * @internal
 */
final class RequestIdSubscriber implements EventSubscriberInterface
{
    /**
     * @param string                      $requestHeader  The header to inspect for the incoming request ID.
     * @param string                      $responseHeader The header that will contain the request ID in the response.
     * @param bool                        $trustRequest   Trust the value from the request? Or generate?
     * @param RequestIdStorageInterface   $idStorage      The request ID storage, used to store the ID from the request or a newly generated ID.
     * @param RequestIdGeneratorInterface $idGenerator    Used to generate a request ID if one isn't present.
     */
    public function __construct(
        private readonly string $requestHeader,
        private readonly string $responseHeader,
        private readonly bool $trustRequest,
        private readonly RequestIdStorageInterface $idStorage,
        private readonly RequestIdGeneratorInterface $idGenerator
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

        $req = $event->getRequest();

        // always give the incoming request priority. If it has the ID in
        // its headers already put that into our ID storage.
        if ($this->trustRequest && $req->headers->get($this->requestHeader) !== null) {
            $this->idStorage->setRequestId($req->headers->get($this->requestHeader));

            return;
        }

        // similarly, if the request ID storage already has an ID set we
        // don't need to do anything other than put it into the request headers
        if ($this->idStorage->getRequestId() !== null) {
            $req->headers->set($this->requestHeader, $this->idStorage->getRequestId());

            return;
        }

        $id = $this->idGenerator->generate();
        $req->headers->set($this->requestHeader, $id);
        $this->idStorage->setRequestId($id);
    }

    public function onResponse(ResponseEvent $event): void
    {
        if ($event->isMainRequest() === false) {
            return;
        }

        if ($this->idStorage->getRequestId() !== null) {
            $event->getResponse()->headers->set($this->responseHeader, $this->idStorage->getRequestId());
        }
    }
}
