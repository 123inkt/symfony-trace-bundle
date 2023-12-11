<?php

declare(strict_types=1);

namespace DR\SymfonyRequestId\Tests\Unit\Generator\TraceContext;

use DR\SymfonyRequestId\Generator\TraceContext\TraceContextIdGenerator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TraceContextIdGenerator::class)]
class TraceContextIdGeneratorTest extends TestCase
{
    public function testGenerateTransactionId(): void
    {
        $generator = new TraceContextIdGenerator();
        static::assertSame(8, strlen($generator->generateTransactionId()));
    }

    public function testGenerateTraceId(): void
    {
        $generator = new TraceContextIdGenerator();
        static::assertSame(16, strlen($generator->generateTraceId()));
    }
}
