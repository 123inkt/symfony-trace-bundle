<?php
declare(strict_types=1);

namespace DR\SymfonyRequestId\Tests\Unit\Monolog;

use DateTimeImmutable;
use DR\SymfonyRequestId\Monolog\RequestIdProcessor;
use DR\SymfonyRequestId\RequestIdStorage;
use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(RequestIdProcessor::class)]
class RequestIdProcessorTest extends TestCase
{
    private RequestIdStorage&MockObject $idStorage;
    private RequestIdProcessor $processor;

    protected function setUp(): void
    {
        $this->idStorage = $this->createMock(RequestIdStorage::class);
        $this->processor = new RequestIdProcessor($this->idStorage);
    }

    public function testProcessorDoesNotSetRequestIdWhenNoIdIsPresent(): void
    {
        if (version_compare((string)Logger::API, '3', 'lt')) {
            self::markTestSkipped('The Monolog at least 3 is required to run this test.');
        }

        $this->idStorage->expects(static::once())->method('getRequestId')->willReturn(null);

        $record = ($this->processor)(new LogRecord(new DateTimeImmutable('now'), 'channel', Level::Info, 'foo'));
        static::assertInstanceOf(LogRecord::class, $record);
        static::assertArrayNotHasKey('request_id', $record->extra);
    }

    public function testProcessorAddsRequestIdWhenIdIsPresent(): void
    {
        if (version_compare((string)Logger::API, '3', 'lt')) {
            self::markTestSkipped('The Monolog at least 3 is required to run this test.');
        }

        $this->idStorage->expects(static::once())->method('getRequestId')->willReturn('abc123');

        $record = ($this->processor)(new LogRecord(new DateTimeImmutable('now'), 'channel', Level::Info, 'foo'));
        static::assertInstanceOf(LogRecord::class, $record);
        static::assertArrayHasKey('request_id', $record->extra);
        static::assertSame('abc123', $record->extra['request_id']);
    }

    public function testProcessorAddsRequestIdWhenIdIsPresentArrayFormat(): void
    {
        if (version_compare((string)Logger::API, '3', 'ge')) {
            self::markTestSkipped('The version 1 or 2 of Monolog is required to run this test.');
        }

        $this->idStorage->expects(static::once())->method('getRequestId')->willReturn('abc123');

        $record = ($this->processor)([]);
        static::assertIsArray($record);
        static::assertArrayHasKey('request_id', $record['extra']);
        static::assertSame('abc123', $record['extra']['request_id']);
    }
}