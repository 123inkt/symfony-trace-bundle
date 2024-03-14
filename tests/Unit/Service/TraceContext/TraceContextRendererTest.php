<?php
declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Tests\Unit\Service\TraceContext;

use DR\SymfonyTraceBundle\Service\TraceContext\TraceContextRenderer;
use DR\SymfonyTraceBundle\TraceContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TraceContextRenderer::class)]
class TraceContextRendererTest extends TestCase
{
    public function testRenderTraceParent(): void
    {
        $context = new TraceContext('00', 'trace_id', 'parent_transaction_id', '01', []);
        $context->setTransactionId('transaction_id');

        $result = TraceContextRenderer::renderTraceParent($context);
        static::assertSame('00-trace_id-transaction_id-01', $result);
    }

    public function testRenderTraceState(): void
    {
        $context = new TraceContext('00', 'trace_id', 'parent_transaction_id', '01', ['key' => 'value']);
        $result  = TraceContextRenderer::renderTraceState($context);
        static::assertSame('key=value', $result);
    }
}
