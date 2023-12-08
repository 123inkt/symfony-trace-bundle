<?php
declare(strict_types=1);

namespace DR\SymfonyRequestId\EventSubscriber;

use DR\SymfonyRequestId\IdGeneratorInterface;
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
 * and applying (and restoring) the trace ID from the stamp on receive.
 * @internal
 */
final class MessageBusSubscriber implements EventSubscriberInterface
{
    private ?string $originalTraceId = null;
    private ?string $originalTransactionId = null;

    public function __construct(private readonly IdStorageInterface $storage, private readonly IdGeneratorInterface $generator)
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
     * When an event is received that contains a traceId from a parent request, this traceId is used.
     * Otherwise, a new traceId is generated. Always generate a new transactionId.
     */
    public function onReceived(WorkerMessageReceivedEvent $event): void
    {
        $stamp = $event->getEnvelope()->last(TraceIdStamp::class);

        // Remember the original tracing ids
        $this->originalTraceId       = $this->storage->getTraceId();
        $this->originalTransactionId = $this->storage->getTransactionId();

        // Set new ids for handling this event
        $this->storage->setTransactionId($this->generator->generate());
        if ($stamp instanceof TraceIdStamp) {
            $this->storage->setTraceId($stamp->traceId);
        } else {
            $this->storage->setTraceId($this->generator->generate());
        }
    }

    /**
     * Invoked when a message is handled, retried, or failed by the worker.
     * Reset both the traceId and the transactionId to the original values.
     */
    public function onHandled(): void
    {
        $this->storage->setTraceId($this->originalTraceId);
        $this->storage->setTransactionId($this->originalTransactionId);
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
