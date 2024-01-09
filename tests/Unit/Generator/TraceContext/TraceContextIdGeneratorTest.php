<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Tests\Unit\Generator\TraceContext;

use DR\SymfonyTraceBundle\Generator\TraceContext\TraceContextIdGenerator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TraceContextIdGenerator::class)]
class TraceContextIdGeneratorTest extends TestCase
{
    public function testGenerateTransactionId(): void
    {
        $generator = new TraceContextIdGenerator();
        static::assertSame(16, strlen($generator->generateTransactionId()));
    }

    public function testGenerateTraceId(): void
    {
        $generator = new TraceContextIdGenerator();
        static::assertSame(32, strlen($generator->generateTraceId()));
    }
}
