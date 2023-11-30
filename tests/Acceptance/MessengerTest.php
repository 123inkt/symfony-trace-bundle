<?php

declare(strict_types=1);

namespace DR\SymfonyRequestId\Tests\Acceptance;

use DR\SymfonyRequestId\RequestIdStorage;
use DR\SymfonyRequestId\Tests\Acceptance\App\Messenger\TestMessage;
use Exception;
use PHPUnit\Framework\Attributes\CoversNothing;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\MessageBusInterface;

#[CoversNothing]
class MessengerTest extends KernelTestCase
{
    /**
     * @throws Exception
     */
    public function testMessageBusShouldAppend(): void
    {
        /** @var RequestIdStorage $storage */
        $storage = self::getContainer()->get(RequestIdStorage::class);
        $storage->setRequestId('foobar');

        /** @var MessageBusInterface $bus */
        $bus = self::getContainer()->get(MessageBusInterface::class);

        $bus->dispatch(new TestMessage());
        $test = true;
    }
}
