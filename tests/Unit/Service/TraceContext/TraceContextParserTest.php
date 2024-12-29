<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Tests\Unit\Service\TraceContext;

use DR\SymfonyTraceBundle\Service\TraceContext\TraceContextParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TraceContextParser::class)]
class TraceContextParserTest extends TestCase
{
    public function testIsValid(): void
    {
        static::assertTrue(TraceContextParser::isValid('00-0af7651916cd43dd8448eb211c80319c-b7ad6b7169203331-00'));
        static::assertFalse(TraceContextParser::isValid('invalid'));
    }

    public function testParseTraceContext(): void
    {
        $traceContext = TraceContextParser::parseTraceContext(
            '00-0af7651916cd43dd8448eb211c80319c-b7ad6b7169203331-00',
            'foo=bar,bar=baz'
        );

        static::assertSame('00', $traceContext->getVersion());
        static::assertSame('0af7651916cd43dd8448eb211c80319c', $traceContext->getTraceId());
        static::assertSame('b7ad6b7169203331', $traceContext->getParentTransactionId());
        static::assertSame('00', $traceContext->getFlags());
        static::assertSame(['foo' => 'bar', 'bar' => 'baz'], $traceContext->getTraceState());
    }

    public function testParseTraceContextLeadingComma(): void
    {
        $traceContext = TraceContextParser::parseTraceContext(
            '00-0af7651916cd43dd8448eb211c80319c-b7ad6b7169203331-00',
            'foo=bar,bar=baz,'
        );

        static::assertSame('00', $traceContext->getVersion());
        static::assertSame('0af7651916cd43dd8448eb211c80319c', $traceContext->getTraceId());
        static::assertSame('b7ad6b7169203331', $traceContext->getParentTransactionId());
        static::assertSame('00', $traceContext->getFlags());
        static::assertSame(['foo' => 'bar', 'bar' => 'baz'], $traceContext->getTraceState());
    }

    public function testParseTraceContextInvalidParent(): void
    {
        $this->expectExceptionMessage('Invalid traceparent header');
        TraceContextParser::parseTraceContext('invalid', '');
    }

    public function testParseTraceContextInvalidState(): void
    {
        $traceContext = TraceContextParser::parseTraceContext(
            '00-0af7651916cd43dd8448eb211c80319c-b7ad6b7169203331-00',
            'invalid'
        );

        static::assertSame('00', $traceContext->getVersion());
        static::assertSame('0af7651916cd43dd8448eb211c80319c', $traceContext->getTraceId());
        static::assertSame('b7ad6b7169203331', $traceContext->getParentTransactionId());
        static::assertSame('00', $traceContext->getFlags());
        static::assertSame([], $traceContext->getTraceState());
    }
}
