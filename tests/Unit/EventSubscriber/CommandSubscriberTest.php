<?php
declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Tests\Unit\EventSubscriber;

use DR\SymfonyTraceBundle\EventSubscriber\CommandSubscriber;
use DR\SymfonyTraceBundle\Service\TraceServiceInterface;
use DR\SymfonyTraceBundle\TraceContext;
use DR\SymfonyTraceBundle\TraceStorageInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\ConsoleEvents;

#[CoversClass(CommandSubscriber::class)]
class CommandSubscriberTest extends TestCase
{
    private TraceStorageInterface&MockObject $storage;
    private TraceServiceInterface&MockObject $service;

    protected function setUp(): void
    {
        $this->storage = $this->createMock(TraceStorageInterface::class);
        $this->service = $this->createMock(TraceServiceInterface::class);
    }

    public function testOnCommandTrace(): void
    {
        $subscriber = new CommandSubscriber($this->storage, $this->service, null);

        $trace = new TraceContext();
        $trace->setTraceId('trace-id');
        $trace->setTransactionId('transaction-id');
        $this->service->expects(static::once())->method('createNewTrace')->willReturn($trace);
        $this->storage->expects(static::once())->method('setTrace')->with($trace);

        $subscriber->onCommand();
    }

    public function testOnCommandStorageHasTrace(): void
    {
        $subscriber = new CommandSubscriber($this->storage, $this->service, null);

        $this->service->expects(static::never())->method('createNewTrace');
        $this->storage->expects(static::once())->method('getTraceId')->willReturn("abc123");

        $subscriber->onCommand();
    }

    public function testOnCommandPresetTraceId(): void
    {
        $subscriber = new CommandSubscriber($this->storage, $this->service, 'test-trace-id');

        $this->storage->expects(static::once())->method('getTraceId')->willReturn(null);
        $this->service->expects(static::once())->method('createTraceFrom')->with('test-trace-id');
        $this->storage->expects(static::once())->method('setTrace');

        $subscriber->onCommand();
    }

    public function testGetSubscribedEvents(): void
    {
        static::assertSame([ConsoleEvents::COMMAND => ['onCommand', 999]], CommandSubscriber::getSubscribedEvents());
    }
}
