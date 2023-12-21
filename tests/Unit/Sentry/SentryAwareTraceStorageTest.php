<?php
declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Tests\Unit\Sentry;

use DR\SymfonyTraceBundle\Sentry\SentryAwareTraceStorage;
use DR\SymfonyTraceBundle\TraceContext;
use DR\SymfonyTraceBundle\TraceStorageInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Sentry\State\HubInterface;
use Sentry\State\Scope;

#[CoversClass(SentryAwareTraceStorage::class)]
class SentryAwareTraceStorageTest extends TestCase
{
    private TraceStorageInterface&MockObject $traceStorage;
    private HubInterface&MockObject $hub;
    private SentryAwareTraceStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->traceStorage = $this->createMock(TraceStorageInterface::class);
        $this->hub          = $this->createMock(HubInterface::class);
        $this->storage      = new SentryAwareTraceStorage($this->traceStorage, $this->hub);
    }

    public function testGetTransactionId(): void
    {
        $this->traceStorage->expects(self::once())->method('getTransactionId')->willReturn('foobar');
        static::assertSame('foobar', $this->storage->getTransactionId());
    }

    public function testSetTransactionId(): void
    {
        $scope = new Scope();

        $this->traceStorage->expects(self::once())->method('setTransactionId')->with('foobar');
        $this->hub->expects(self::once())->method('configureScope')
            ->willReturnCallback(static fn(callable $callback) => $callback($scope));

        $this->storage->setTransactionId('foobar');
        self::assertScopeHasTag($scope, 'transaction_id', 'foobar');
    }

    public function testGetTraceId(): void
    {
        $this->traceStorage->expects(self::once())->method('getTraceId')->willReturn('foobar');
        static::assertSame('foobar', $this->storage->getTraceId());
    }

    public function testSetTraceId(): void
    {
        $scope = new Scope();

        $this->traceStorage->expects(self::once())->method('setTraceId')->with('foobar');
        $this->hub->expects(self::once())->method('configureScope')
            ->willReturnCallback(static fn(callable $callback) => $callback($scope));

        $this->storage->setTraceId('foobar');
        self::assertScopeHasTag($scope, 'trace_id', 'foobar');
    }

    public function testGetTrace(): void
    {
        $traceContext = new TraceContext();

        $this->traceStorage->expects(self::once())->method('getTrace')->willReturn($traceContext);
        static::assertSame($traceContext, $this->storage->getTrace());
    }

    public function testSetTraceShouldSetValues(): void
    {
        $scope        = new Scope();
        $traceContext = new TraceContext();
        $traceContext->setTraceId('trace-id-a');
        $traceContext->setTransactionId('transaction-id-b');

        $this->traceStorage->expects(self::once())->method('setTrace')->with($traceContext);
        $this->hub->expects(self::once())->method('configureScope')
            ->willReturnCallback(static fn(callable $callback) => $callback($scope));

        $this->storage->setTrace($traceContext);
        self::assertScopeHasTag($scope, 'trace_id', 'trace-id-a');
        self::assertScopeHasTag($scope, 'transaction_id', 'transaction-id-b');
    }

    public function testSetTraceShouldRemoveValues(): void
    {
        $scope = new Scope();
        $scope->setTag('trace_id', 'trace-id-a');
        $scope->setTag('transaction_id', 'transaction-id-b');
        $traceContext = new TraceContext();
        $traceContext->setTraceId(null);
        $traceContext->setTransactionId(null);

        $this->traceStorage->expects(self::once())->method('setTrace')->with($traceContext);
        $this->hub->expects(self::once())->method('configureScope')
            ->willReturnCallback(static fn(callable $callback) => $callback($scope));

        $this->storage->setTrace($traceContext);
        self::assertScopeDoesNotHaveTag($scope, 'trace_id');
        self::assertScopeDoesNotHaveTag($scope, 'transaction_id');
    }

    private static function assertScopeHasTag(Scope $scope, string $key, string $value): void
    {
        $class    = new ReflectionClass($scope);
        $property = $class->getProperty('tags');
        $property->setAccessible(true);

        $tags = $property->getValue($scope);
        static::assertIsArray($tags);
        static::assertArrayHasKey($key, $tags);
        static::assertSame($value, $tags[$key]);
    }

    private static function assertScopeDoesNotHaveTag(Scope $scope, string $key): void
    {
        $class    = new ReflectionClass($scope);
        $property = $class->getProperty('tags');
        $property->setAccessible(true);

        $tags = $property->getValue($scope);
        static::assertIsArray($tags);
        static::assertArrayNotHasKey($key, $tags);
    }
}
