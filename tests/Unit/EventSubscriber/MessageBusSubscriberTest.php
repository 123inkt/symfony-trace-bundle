<?php
declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Tests\Unit\EventSubscriber;

use DR\SymfonyTraceBundle\EventSubscriber\MessageBusSubscriber;
use DR\SymfonyTraceBundle\Generator\TraceContext\TraceContextIdGenerator;
use DR\SymfonyTraceBundle\Generator\TraceId\TraceIdGeneratorInterface;
use DR\SymfonyTraceBundle\TraceContext;
use DR\SymfonyTraceBundle\TraceId;
use DR\SymfonyTraceBundle\TraceStorageInterface;
use DR\SymfonyTraceBundle\Messenger\TraceIdStamp;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\SendMessageToTransportsEvent;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageRetriedEvent;
use function DR\PHPUnitExtensions\Mock\consecutive;

#[CoversClass(MessageBusSubscriber::class)]
class MessageBusSubscriberTest extends TestCase
{
    private TraceStorageInterface&MockObject $storage;
    private TraceIdGeneratorInterface&MockObject $traceIdGenerator;
    private TraceContextIdGenerator&MockObject $traceContextGenerator;
    private MessageBusSubscriber $subscriber;
    private Envelope $envelope;

    protected function setUp(): void
    {
        $this->envelope              = new Envelope(new stdClass());
        $this->storage               = $this->createMock(TraceStorageInterface::class);
        $this->traceIdGenerator      = $this->createMock(TraceIdGeneratorInterface::class);
        $this->traceContextGenerator = $this->createMock(TraceContextIdGenerator::class);
        $this->subscriber            = new MessageBusSubscriber(
            TraceId::TRACEMODE,
            $this->storage,
            $this->traceIdGenerator,
            $this->traceContextGenerator
        );
    }

    /**
     * When sending an event and the process has a traceId, this id is passed along.
     */
    public function testOnSendWithTraceId(): void
    {
        $event = new SendMessageToTransportsEvent($this->envelope, []);

        $this->storage->expects(self::once())->method('getTraceId')->willReturn('trace-id');

        $this->subscriber->onSend($event);
        self::assertSame('trace-id', $event->getEnvelope()->last(TraceIdStamp::class)?->traceId);
    }

    /**
     * When sending an event and the process has no traceId, no traceId is passed along.
     */
    public function testOnSendWithoutTraceId(): void
    {
        $event = new SendMessageToTransportsEvent($this->envelope, []);

        $this->storage->expects(self::once())->method('getTraceId')->willReturn(null);

        $this->subscriber->onSend($event);
        self::assertNull($event->getEnvelope()->last(TraceIdStamp::class));
    }

    /**
     * An event is received with traceIdStamp.
     * A new transactionId is generated, but the stamp's traceId is set into the storage.
     * On handled, the original (null) values are set back into the storage
     */
    public function testOnReceivedAndHandledWithTraceId(): void
    {
        $envelope = $this->envelope->with(new TraceIdStamp('trace-id'));
        $event    = new WorkerMessageReceivedEvent($envelope, 'receiver');

        $this->storage->expects(self::exactly(2))
            ->method('setTraceId')
            ->with(...consecutive(['trace-id'], [null]));

        $this->subscriber->onReceived($event);
        $this->subscriber->onHandled();
    }

    /**
     * An event is received without traceIdStamp.
     * New transactionId and traceId values are generated,
     * on handled the original (null) values are set back into the storage
     */
    public function testOnReceivedAndHandledWithoutTraceId(): void
    {
        $event = new WorkerMessageReceivedEvent($this->envelope, 'receiver');

        $this->traceIdGenerator->expects(self::exactly(2))->method('generate')->willReturn('123ABC', 'ABC123');
        $this->storage->expects(self::exactly(2))->method('setTraceId')->with(...consecutive(['123ABC'], [null]));
        $this->storage->expects(self::exactly(2))->method('setTransactionId')->with(...consecutive(['ABC123'], [null]));

        $this->subscriber->onReceived($event);
        $this->subscriber->onHandled();
    }

    /**
     * An event is received without traceIdStamp.
     * New transactionId and traceId values are generated,
     * on handled the original (null) values are set back into the storage
     */
    public function testOnReceivedAndHandledWithoutTraceContext(): void
    {
        $this->subscriber = new MessageBusSubscriber(
            TraceContext::TRACEMODE,
            $this->storage,
            $this->traceIdGenerator,
            $this->traceContextGenerator
        );

        $event = new WorkerMessageReceivedEvent($this->envelope, 'receiver');

        $this->traceContextGenerator->expects(self::once())->method('generateTraceId')->willReturn('123ABC');
        $this->traceContextGenerator->expects(self::once())->method('generateTransactionId')->willReturn('ABC123');
        $this->storage->expects(self::exactly(2))->method('setTraceId')->with(...consecutive(['123ABC'], [null]));
        $this->storage->expects(self::exactly(2))->method('setTransactionId')->with(...consecutive(['ABC123'], [null]));

        $this->subscriber->onReceived($event);
        $this->subscriber->onHandled();
    }

    public function testGetSubscribedEvents(): void
    {
        $expected = [
            SendMessageToTransportsEvent::class => ['onSend'],
            WorkerMessageReceivedEvent::class   => ['onReceived'],
            WorkerMessageHandledEvent::class    => ['onHandled'],
            WorkerMessageRetriedEvent::class    => ['onHandled'],
            WorkerMessageFailedEvent::class     => ['onHandled'],
        ];
        static::assertSame($expected, MessageBusSubscriber::getSubscribedEvents());
    }
}
