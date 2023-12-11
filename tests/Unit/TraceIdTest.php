<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Tests\Unit;

use DigitalRevolution\AccessorPairConstraint\AccessorPairAsserter;
use DR\SymfonyTraceBundle\TraceId;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TraceId::class)]
class TraceIdTest extends TestCase
{
    use AccessorPairAsserter;

    public function testAccessors(): void
    {
        self::assertAccessorPairs(TraceId::class);
    }
}
