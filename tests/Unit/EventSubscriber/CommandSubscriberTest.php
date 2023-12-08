<?php
declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Tests\Unit\EventSubscriber;

use DR\SymfonyTraceBundle\EventSubscriber\CommandSubscriber;
use DR\SymfonyTraceBundle\IdGeneratorInterface;
use DR\SymfonyTraceBundle\IdStorageInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\ConsoleEvents;

#[CoversClass(CommandSubscriber::class)]
class CommandSubscriberTest extends TestCase
{
    private IdStorageInterface&MockObject $idStorage;
    private IdGeneratorInterface&MockObject $generator;
    private CommandSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->idStorage = $this->createMock(IdStorageInterface::class);
        $this->generator = $this->createMock(IdGeneratorInterface::class);
        $this->subscriber = new CommandSubscriber($this->idStorage, $this->generator);
    }

    public function testOnCommand(): void
    {
        $this->generator->expects(self::exactly(2))->method('generate')->willReturn('trace-id', 'transaction-id');
        $this->idStorage->expects(self::once())->method('setTraceId')->with('trace-id');
        $this->idStorage->expects(self::once())->method('setTransactionId')->with('transaction-id');

        $this->subscriber->onCommand();
    }

    public function testGetSubscribedEvents(): void
    {
        static::assertSame([ConsoleEvents::COMMAND => ['onCommand', 999]], CommandSubscriber::getSubscribedEvents());
    }
}
