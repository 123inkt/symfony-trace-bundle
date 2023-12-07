<?php
declare(strict_types=1);

namespace DR\SymfonyRequestId\Tests\Unit\EventSubscriber;

use DR\SymfonyRequestId\EventSubscriber\CommandSubscriber;
use DR\SymfonyRequestId\IdGeneratorInterface;
use DR\SymfonyRequestId\IdStorageInterface;
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
        parent::setUp();
        $this->idStorage = $this->createMock(IdStorageInterface::class);
        $this->generator = $this->createMock(IdGeneratorInterface::class);
        $this->subscriber = new CommandSubscriber($this->idStorage, $this->generator);
    }

    public function testOnCommand(): void
    {
        $this->generator->expects(self::once())->method('generate')->willReturn('request-id');
        $this->idStorage->expects(self::once())->method('setTraceId')->with('request-id');

        $this->subscriber->onCommand();
    }

    public function testGetSubscribedEvents(): void
    {
        static::assertSame([ConsoleEvents::COMMAND => ['onCommand', 999]], CommandSubscriber::getSubscribedEvents());
    }
}
