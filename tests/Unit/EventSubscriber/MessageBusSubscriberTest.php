<?php
declare(strict_types=1);

namespace DR\SymfonyRequestId\Tests\Unit\EventSubscriber;

use DR\SymfonyRequestId\EventSubscriber\MessageBusSubscriber;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MessageBusSubscriber::class)]
class MessageBusSubscriberTest extends TestCase
{

    public function testOnSend(): void
    {
    }

    public function testGetSubscribedEvents(): void
    {
    }

    public function testOnReceived(): void
    {
    }

    public function testOnHandled(): void
    {
    }
}
