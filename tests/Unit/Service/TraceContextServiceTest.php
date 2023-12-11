<?php

declare(strict_types=1);

namespace DR\SymfonyRequestId\Tests\Unit\Service;

use DR\SymfonyRequestId\Service\TraceContextService;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(TraceContextService::class)]
class TraceContextServiceTest extends TestCase
{
    private TraceContextService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new TraceContextService();
    }

    #[DataProvider('provideTraceParent')]
    public function testValidateTraceParent(string $value, bool $expectedSuccess): void
    {
        static::assertSame($expectedSuccess, $this->service->validateTraceParent($value));
    }

    public static function provideTraceParent(): Generator
    {
        yield ['00-0af7651916cd43dd8448eb211c80319c-b7ad6b7169203331-01', true];
        yield ['00-0af7651916cd43dd8448eb211c80319c-00f067aa0ba902b7-01', true];
        yield ['00-0af7651916cd43dd8448eb211c80319c-b9c7c989f97918e1-01', true];
        yield ['00-4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7-01', true];
        yield ['00-4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7-00', true];

        yield ['00-00f067aa0ba902b7-4bf92f3577b34da6a3ce929d0e0e4736-00', false];
        yield ['000-4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7-00', false];
        yield ['00-4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7-000', false];
    }
}
