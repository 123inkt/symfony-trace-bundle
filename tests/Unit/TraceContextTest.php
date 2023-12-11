<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Tests\Unit;

use DigitalRevolution\AccessorPairConstraint\AccessorPairAsserter;
use DigitalRevolution\AccessorPairConstraint\Constraint\ConstraintConfig;
use DR\SymfonyTraceBundle\TraceContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TraceContext::class)]
class TraceContextTest extends TestCase
{
    use AccessorPairAsserter;

    public function testAccessors(): void
    {
        self::assertAccessorPairs(TraceContext::class, (new ConstraintConfig())->setExcludedMethods(['setFlags']));
    }

    public function testSetFlags(): void
    {
        $traceContext = new TraceContext();
        $traceContext->setFlags('01');
        self::assertSame('01', $traceContext->getFlags());
    }
}
