<?php
declare(strict_types=1);

namespace DR\SymfonyRequestId\Tests\Unit\EventSubscriber;

use DR\SymfonyRequestId\EventSubscriber\CommandSubscriber;
use DR\SymfonyRequestId\RequestIdGenerator;
use DR\SymfonyRequestId\RequestIdStorage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\ConsoleEvents;

#[CoversClass(CommandSubscriber::class)]
class CommandSubscriberTest extends TestCase
{
    private RequestIdStorage&MockObject $requestIdStorage;
    private RequestIdGenerator&MockObject $generator;
    private CommandSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requestIdStorage = $this->createMock(RequestIdStorage::class);
        $this->generator        = $this->createMock(RequestIdGenerator::class);
        $this->subscriber       = new CommandSubscriber($this->requestIdStorage, $this->generator);
    }

    public function testOnCommand(): void
    {
        $this->generator->expects(self::once())->method('generate')->willReturn('request-id');
        $this->requestIdStorage->expects(self::once())->method('setRequestId')->with('request-id');

        $this->subscriber->onCommand();
    }

    public function testGetSubscribedEvents(): void
    {
        static::assertSame([ConsoleEvents::COMMAND => ['onCommand', 999]], CommandSubscriber::getSubscribedEvents());
    }
}
