<?php

declare(strict_types=1);

namespace DR\SymfonyRequestId\Tests\Functional;

use DR\SymfonyRequestId\Messenger\RequestIdStamp;
use DR\SymfonyRequestId\RequestIdStorageInterface;
use DR\SymfonyRequestId\Tests\Functional\App\Messenger\TestMessage;
use DR\SymfonyRequestId\Tests\Functional\App\Service\TestRequestIdStorage;
use DR\Utils\Assert;
use Exception;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Messenger\Worker;

#[CoversNothing]
class MessengerTest extends KernelTestCase
{
    private TestRequestIdStorage $storage;
    private MessageBusInterface $bus;
    private EventDispatcherInterface $dispatcher;
    private InMemoryTransport $transport;
    private ClockInterface&MockObject $clock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storage    = Assert::isInstanceOf(self::getContainer()->get(RequestIdStorageInterface::class), TestRequestIdStorage::class);
        $this->bus        = Assert::isInstanceOf(self::getContainer()->get(MessageBusInterface::class), MessageBusInterface::class);
        $this->dispatcher = Assert::isInstanceOf(self::getContainer()->get('event_dispatcher'), EventDispatcherInterface::class);
        $this->transport  = Assert::isInstanceOf(self::getContainer()->get('messenger.transport.test_transport'), InMemoryTransport::class);
        $this->clock      = $this->createMock(ClockInterface::class);
    }

    /**
     * @throws Exception
     */
    public function testMessageBusShouldAddAndHandlerStamp(): void
    {
        $this->storage->setRequestId('foobar');

        // ** dispatch **
        $this->bus->dispatch(new TestMessage());
        self::assertTransportHasEnvelopWithRequestIdStamp($this->transport);

        // ** consume ** (simulate worker)
        (new Worker([$this->transport], $this->bus, $this->dispatcher, clock: $this->clock))->run();
        self::assertStorageHasRequestId($this->storage);
    }

    private static function assertTransportHasEnvelopWithRequestIdStamp(InMemoryTransport $transport): void
    {
        static::assertCount(1, $transport->getSent());

        $envelop = Assert::isArray($transport->get())[0];
        static::assertInstanceOf(Envelope::class, $envelop);

        $stamp = $envelop->last(RequestIdStamp::class);
        static::assertInstanceOf(RequestIdStamp::class, $stamp);
        static::assertSame('foobar', $stamp->requestId);
    }

    private static function assertStorageHasRequestId(TestRequestIdStorage $storage): void
    {
        // expect 5: 1x dispatch, 1x receive, 1x handled, 1x dispatch, 1x receive
        static::assertSame(5, $storage->getRequestIdCount);

        // expect 3: 1x dispatch, 1x receive, 1x handled
        static::assertSame(3, $storage->setRequestIdCount);
    }
}
