<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Tests\Unit\Twig;

use DR\SymfonyTraceBundle\TraceStorage;
use DR\SymfonyTraceBundle\Twig\TraceExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Throwable;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

#[CoversClass(TraceExtension::class)]
class TraceIdExtensionTest extends TestCase
{
    private const TEMPLATE = 'Trace: {{ trace_id() }}. Transaction: {{ transaction_id() }}.';

    private Environment $environment;
    private TraceStorage $storage;

    protected function setUp(): void
    {
        $this->storage = new TraceStorage();
        $this->environment = new Environment(new ArrayLoader(['test' => self::TEMPLATE]));
        $this->environment->addExtension(new TraceExtension($this->storage));
    }

    /**
     * @throws Throwable
     */
    public function testTwigTraceFunction(): void
    {
        $this->storage->setTraceId('abc123');
        $this->storage->setTransactionId('123');

        $result = $this->environment->render('test');
        static::assertSame($result, 'Trace: abc123. Transaction: 123.');
    }
}
