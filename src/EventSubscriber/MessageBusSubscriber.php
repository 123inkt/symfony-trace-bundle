<?php
declare(strict_types=1);

namespace DR\SymfonyRequestId\EventSubscriber;

use DR\SymfonyRequestId\Messenger\TraceIdStamp;
use DR\SymfonyRequestId\IdStorageInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\SendMessageToTransportsEvent;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageRetriedEvent;

/**
 * Listen for messages being sent and received by the message bus. Adding a stamp on send
 * and applying (and restoring) the request ID from the stamp on receive.
 * @internal
 */
class MessageBusSubscriber implements EventSubscriberInterface
{
    private string|false|null $originalTraceId = false;

    public function __construct(private readonly IdStorageInterface $storage)
    {
    }

    /**
     * Invoked just before message is send to the message bus.
     */
    public function onSend(SendMessageToTransportsEvent $event): void
    {
        $traceId = $this->storage->getTraceId();
        if ($traceId !== null) {
            $event->setEnvelope($event->getEnvelope()->with(new TraceIdStamp($traceId)));
        }
    }

    /**
     * Invoked when a message is received by the worker. Also invoked when message is retried.
     */
    public function onReceived(WorkerMessageReceivedEvent $event): void
    {
        $stamp = $event->getEnvelope()->last(TraceIdStamp::class);
        if ($stamp instanceof TraceIdStamp) {
            $this->originalTraceId = $this->storage->getTraceId();
            $this->storage->setTraceId($stamp->traceId);
        }
    }

    /**
     * Invoked when a message is handled, retried, or failed by the worker.
     */
    public function onHandled(): void
    {
        if ($this->originalTraceId !== false) {
            $this->storage->setTraceId($this->originalTraceId);
            $this->originalTraceId = false;
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
