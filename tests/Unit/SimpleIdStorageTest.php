<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Tests\Unit;

use DR\SymfonyTraceBundle\SimpleIdStorage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SimpleIdStorage::class)]
class SimpleIdStorageTest extends TestCase
{
    public function testGetTraceIdReturnsTheSameValueThatWasSet(): void
    {
        $storage = new SimpleIdStorage();

        static::assertNull($storage->getTraceId());
        $storage->setTraceId('test');
        static::assertSame('test', $storage->getTraceId());
    }

    public function testNullCanBePassedToSetTraceIdToClearIt(): void
    {
        $storage = new SimpleIdStorage();
        $storage->setTraceId('test');

        $storage->setTraceId(null);

        static::assertNull($storage->getTraceId());
    }

    public function testGetTransactionIdReturnsTheSameValueThatWasSet(): void
    {
        $storage = new SimpleIdStorage();

        static::assertNull($storage->getTransactionId());
        $storage->setTransactionId('test');
        static::assertSame('test', $storage->getTransactionId());
    }

    public function testNullCanBePassedToSetTransactionIdToClearIt(): void
    {
        $storage = new SimpleIdStorage();
        $storage->setTransactionId('test');

        $storage->setTransactionId(null);

        static::assertNull($storage->getTransactionId());
    }
}
