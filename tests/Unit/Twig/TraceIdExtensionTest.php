<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Tests\Unit\Twig;

use DR\SymfonyTraceBundle\SimpleIdStorage;
use DR\SymfonyTraceBundle\Twig\TraceIdExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Throwable;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

#[CoversClass(TraceIdExtension::class)]
class TraceIdExtensionTest extends TestCase
{
    private const TEMPLATE = 'Trace: {{ trace_id() }}. Transaction: {{ transaction_id() }}.';

    private Environment $environment;
    private SimpleIdStorage $storage;

    protected function setUp(): void
    {
        $this->storage = new SimpleIdStorage();
        $this->environment = new Environment(new ArrayLoader(['test' => self::TEMPLATE]));
        $this->environment->addExtension(new TraceIdExtension($this->storage));
    }

    /**
     * @throws Throwable
     */
    public function testTwigTraceIdFunction(): void
    {
        $this->storage->setTraceId('abc123');
        $this->storage->setTransactionId('123');

        $result = $this->environment->render('test');
        static::assertSame($result, 'Trace: abc123. Transaction: 123.');
    }
}
