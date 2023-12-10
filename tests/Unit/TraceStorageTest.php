<?php

declare(strict_types=1);

namespace DR\SymfonyRequestId\Tests\Unit;

use DR\SymfonyRequestId\TraceStorage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TraceStorage::class)]
class TraceStorageTest extends TestCase
{
    public function testGetTraceIdReturnsTheSameValueThatWasSet(): void
    {
        $storage = new TraceStorage();

        static::assertNull($storage->getTraceId());
        $storage->setTraceId('test');
        static::assertSame('test', $storage->getTraceId());
    }

    public function testNullCanBePassedToSetTraceIdToClearIt(): void
    {
        $storage = new TraceStorage();
        $storage->setTraceId('test');

        $storage->setTraceId(null);

        static::assertNull($storage->getTraceId());
    }

    public function testGetTransactionIdReturnsTheSameValueThatWasSet(): void
    {
        $storage = new TraceStorage();

        static::assertNull($storage->getTransactionId());
        $storage->setTransactionId('test');
        static::assertSame('test', $storage->getTransactionId());
    }

    public function testNullCanBePassedToSetTransactionIdToClearIt(): void
    {
        $storage = new TraceStorage();
        $storage->setTransactionId('test');

        $storage->setTransactionId(null);

        static::assertNull($storage->getTransactionId());
    }
}
