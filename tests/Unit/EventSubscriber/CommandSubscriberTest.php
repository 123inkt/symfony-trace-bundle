<?php
declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Tests\Unit\EventSubscriber;

use DR\SymfonyTraceBundle\EventSubscriber\CommandSubscriber;
use DR\SymfonyTraceBundle\Generator\TraceContext\TraceContextIdGenerator;
use DR\SymfonyTraceBundle\Generator\TraceId\TraceIdGeneratorInterface;
use DR\SymfonyTraceBundle\TraceContext;
use DR\SymfonyTraceBundle\TraceId;
use DR\SymfonyTraceBundle\TraceStorageInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\ConsoleEvents;

#[CoversClass(CommandSubscriber::class)]
class CommandSubscriberTest extends TestCase
{
    private TraceStorageInterface&MockObject $traceStorage;
    private TraceIdGeneratorInterface&MockObject $traceIdGenerator;
    private TraceContextIdGenerator&MockObject $traceContextGenerator;

    protected function setUp(): void
    {
        $this->traceStorage          = $this->createMock(TraceStorageInterface::class);
        $this->traceIdGenerator      = $this->createMock(TraceIdGeneratorInterface::class);
        $this->traceContextGenerator = $this->createMock(TraceContextIdGenerator::class);
    }

    public function testOnCommandTraceId(): void
    {
        $subscriber = new CommandSubscriber(
            TraceId::TRACEMODE,
            $this->traceStorage,
            $this->traceIdGenerator,
            $this->traceContextGenerator
        );

        $this->traceIdGenerator->expects(self::exactly(2))->method('generate')->willReturn('trace-id', 'transaction-id');
        $this->traceContextGenerator->expects(self::never())->method('generateTraceId');
        $this->traceContextGenerator->expects(self::never())->method('generateTransactionId');

        $traceId = new TraceId();
        $traceId->setTraceId('trace-id');
        $traceId->setTransactionId('transaction-id');
        $this->traceStorage->expects(self::once())->method('setTrace')->with($traceId);

        $subscriber->onCommand();
    }

    public function testOnCommandTraceContext(): void
    {
        $subscriber = new CommandSubscriber(
            TraceContext::TRACEMODE,
            $this->traceStorage,
            $this->traceIdGenerator,
            $this->traceContextGenerator
        );

        $this->traceIdGenerator->expects(self::never())->method('generate');
        $this->traceContextGenerator->expects(self::once())->method('generateTraceId')->willReturn('trace-id');
        $this->traceContextGenerator->expects(self::once())->method('generateTransactionId')->willReturn('transaction-id');

        $traceContext = new TraceContext();
        $traceContext->setTraceId('trace-id');
        $traceContext->setTransactionId('transaction-id');
        $this->traceStorage->expects(self::once())->method('setTrace')->with($traceContext);

        $subscriber->onCommand();
    }

    public function testGetSubscribedEvents(): void
    {
        static::assertSame([ConsoleEvents::COMMAND => ['onCommand', 999]], CommandSubscriber::getSubscribedEvents());
    }
}
