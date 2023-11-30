<?php
declare(strict_types=1);

namespace DR\SymfonyRequestId\EventSubscriber;

use DR\SymfonyRequestId\Messenger\RequestIdStamp;
use DR\SymfonyRequestId\RequestIdStorage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\SendMessageToTransportsEvent;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageRetriedEvent;

class MessageBusSubscriber implements EventSubscriberInterface
{
    private string|false|null $originalRequestId = false;

    public function __construct(private readonly RequestIdStorage $storage)
    {
    }

    /**
     * Invoked just before message is send to the message bus.
     */
    public function onSend(SendMessageToTransportsEvent $event): void
    {
        $requestId = $this->storage->getRequestId();
        if ($requestId !== null) {
            $event->setEnvelope($event->getEnvelope()->with(new RequestIdStamp($requestId)));
        }
    }

    /**
     * Invoked when a message is received by the worker. Also invoked when message is retried.
     */
    public function onReceived(WorkerMessageReceivedEvent $event): void
    {
        $stamp = $event->getEnvelope()->last(RequestIdStamp::class);
        if ($stamp instanceof RequestIdStamp) {
            $this->originalRequestId = $this->storage->getRequestId();
            $this->storage->setRequestId($stamp->requestId);
        }
    }

    /**
     * Invoked when a message is handled, retried, or failed by the worker.
     */
    public function onHandled(): void
    {
        if ($this->originalRequestId !== false) {
            $this->storage->setRequestId($this->originalRequestId);
            $this->originalRequestId = false;
        }
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            SendMessageToTransportsEvent::class => ['onSend'],
            WorkerMessageReceivedEvent::class   => ['onReceived'],
            WorkerMessageHandledEvent::class    => ['onHandled'],
            WorkerMessageRetriedEvent::class    => ['onHandled'],
            WorkerMessageFailedEvent::class     => ['onHandled'],
        ];
    }
}
