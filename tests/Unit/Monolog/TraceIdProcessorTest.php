<?php
declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Tests\Unit\Monolog;

use DateTimeImmutable;
use DR\SymfonyTraceBundle\Monolog\TraceProcessor;
use DR\SymfonyTraceBundle\TraceStorageInterface;
use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(TraceProcessor::class)]
class TraceIdProcessorTest extends TestCase
{
    private TraceStorageInterface&MockObject $idStorage;
    private TraceProcessor $processor;

    protected function setUp(): void
    {
        $this->idStorage = $this->createMock(TraceStorageInterface::class);
        $this->processor = new TraceProcessor($this->idStorage);
    }

    public function testProcessorDoesNotSetTraceIdWhenNoIdIsPresent(): void
    {
        if (version_compare((string)Logger::API, '3', 'lt')) {
            self::markTestSkipped('The Monolog at least 3 is required to run this test.');
        }

        $this->idStorage->expects(static::once())->method('getTraceId')->willReturn(null);

        $record = ($this->processor)(new LogRecord(new DateTimeImmutable('now'), 'channel', Level::Info, 'foo'));
        static::assertInstanceOf(LogRecord::class, $record);
        static::assertArrayNotHasKey('trace_id', $record->extra);
    }

    public function testProcessorAddsTraceIdWhenIdIsPresent(): void
    {
        if (version_compare((string)Logger::API, '3', 'lt')) {
            self::markTestSkipped('The Monolog at least 3 is required to run this test.');
        }

        $this->idStorage->expects(static::once())->method('getTraceId')->willReturn('abc123');

        $record = ($this->processor)(new LogRecord(new DateTimeImmutable('now'), 'channel', Level::Info, 'foo'));
        static::assertInstanceOf(LogRecord::class, $record);
        static::assertArrayHasKey('trace_id', $record->extra);
        static::assertSame('abc123', $record->extra['trace_id']);
    }

    public function testProcessorAddsTraceIdWhenIdIsPresentArrayFormat(): void
    {
        if (version_compare((string)Logger::API, '3', 'ge')) {
            self::markTestSkipped('The version 1 or 2 of Monolog is required to run this test.');
        }

        $this->idStorage->expects(static::once())->method('getTraceId')->willReturn('abc123');

        $record = ($this->processor)([]);
        static::assertIsArray($record['extra']);
        static::assertArrayHasKey('trace_id', $record['extra']);
        static::assertSame('abc123', $record['extra']['trace_id']);
    }
}
