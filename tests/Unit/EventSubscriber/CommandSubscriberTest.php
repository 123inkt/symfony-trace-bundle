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
        $subscriber = new CommandSubscriber($this->storage, $this->service);

        $trace = new TraceContext();
        $trace->setTraceId('trace-id');
        $trace->setTransactionId('transaction-id');
        $this->service->expects(static::once())->method('createNewTrace')->willReturn($trace);
        $this->storage->expects(static::once())->method('setTrace')->with($trace);

        $subscriber->onCommand();
    }

    public function testGetSubscribedEvents(): void
    {
        static::assertSame([ConsoleEvents::COMMAND => ['onCommand', 999]], CommandSubscriber::getSubscribedEvents());
    }
}
