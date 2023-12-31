<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Tests\Unit\Generator\TraceId;

use DR\SymfonyTraceBundle\Generator\TraceId\SymfonyUuid4Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SymfonyUuid4Generator::class)]
class SymfonyUuid4GeneratorTest extends TestCase
{
    public function testIsSupported(): void
    {
        static::assertTrue(SymfonyUuid4Generator::isSupported());
    }

    public function testGenerate(): void
    {
        // we're not going to mock anything here, I'm more
        // interested in making sure we're using the library
        // correctly than worry about mocking method calls.
        $generator = new SymfonyUuid4Generator();

        static::assertNotEmpty($generator->generateTraceId());
        static::assertNotEmpty($generator->generateTransactionId());
    }
}
