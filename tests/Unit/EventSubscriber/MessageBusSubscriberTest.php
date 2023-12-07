<?php
declare(strict_types=1);

namespace DR\SymfonyRequestId\Tests\Unit\EventSubscriber;

use DR\SymfonyRequestId\EventSubscriber\MessageBusSubscriber;
use DR\SymfonyRequestId\Messenger\TraceIdStamp;
use DR\SymfonyRequestId\IdStorageInterface;
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
    private IdStorageInterface&MockObject $storage;
    private MessageBusSubscriber $subscriber;
    private Envelope $envelope;

    protected function setUp(): void
    {
        parent::setUp();
        $this->envelope   = new Envelope(new stdClass());
        $this->storage    = $this->createMock(IdStorageInterface::class);
        $this->subscriber = new MessageBusSubscriber($this->storage);
    }

    public function testOnSendWithTraceId(): void
    {
        $event = new SendMessageToTransportsEvent($this->envelope, []);

        $this->storage->expects(self::once())->method('getTraceId')->willReturn('request-id');

        $this->subscriber->onSend($event);
        self::assertSame('request-id', $event->getEnvelope()->last(TraceIdStamp::class)?->traceId);
    }

    public function testOnSendWithoutTraceId(): void
    {
        $event = new SendMessageToTransportsEvent($this->envelope, []);

        $this->storage->expects(self::once())->method('getTraceId')->willReturn(null);

        $this->subscriber->onSend($event);
        self::assertNull($event->getEnvelope()->last(TraceIdStamp::class));
    }

    public function testOnReceivedAndHandledWithTraceId(): void
    {
        $envelope = $this->envelope->with(new TraceIdStamp('request-id'));
        $event    = new WorkerMessageReceivedEvent($envelope, 'receiver');

        $this->storage->expects(self::exactly(2))
            ->method('setTraceId')
            ->with(...consecutive(['request-id'], [null]));

        $this->subscriber->onReceived($event);
        $this->subscriber->onHandled();
    }

    public function testOnReceivedAndHandledWithoutTraceId(): void
    {
        $event = new WorkerMessageReceivedEvent($this->envelope, 'receiver');

        $this->storage->expects(self::never())->method('setTraceId');

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
