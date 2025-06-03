<?php
declare(strict_types=1);

namespace DR\SymfonyTraceBundle\EventSubscriber;

use DR\SymfonyTraceBundle\Generator\TraceIdGeneratorInterface;
use DR\SymfonyTraceBundle\Messenger\TraceStamp;
use DR\SymfonyTraceBundle\TraceStorageInterface;
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

    public function __construct(private readonly TraceStorageInterface $storage, private readonly TraceIdGeneratorInterface $generator)
    {
    }

    /**
     * Invoked just before message is send to the message bus.
     */
    public function onSend(SendMessageToTransportsEvent $event): void
    {
        if ($this->storage->getTraceId() === null) {
            return;
        }

        // pass our current transactionId to the async handler as 'parentTransactionId'
        $trace = clone $this->storage->getTrace();
        $trace->setParentTransactionId($this->storage->getTransactionId());
        $trace->setTransactionId(null);

        $event->setEnvelope($event->getEnvelope()->with(new TraceStamp($trace)));
    }

    /**
     * Invoked when a message is received by the worker. Also invoked when message is retried.
     * When an event is received that contains a traceId from a parent request, this traceId is used.
     * Otherwise, a new traceId is generated. Always generate a new transactionId.
     */
    public function onReceived(WorkerMessageReceivedEvent $event): void
    {
        $stamp = $event->getEnvelope()->last(TraceStamp::class);

        // Remember the original tracing ids
        $this->originalTraceId       = $this->storage->getTraceId();
        $this->originalTransactionId = $this->storage->getTransactionId();

        // Set new ids for handling this event
        $this->storage->setTransactionId($this->generator->generateTransactionId());
        if ($stamp instanceof TraceStamp) {
            $this->storage->setTraceId($stamp->trace->getTraceId());
        } else {
            $this->storage->setTraceId($this->generator->generateTraceId());
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
