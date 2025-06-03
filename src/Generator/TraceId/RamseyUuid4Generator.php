<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Generator\TraceId;

use DR\SymfonyTraceBundle\Generator\TraceIdGeneratorInterface;
use Ramsey\Uuid\UuidFactory;
use Ramsey\Uuid\UuidFactoryInterface;

/**
 * Uses `ramsey/uuid` to generator v4 UUIDs for request ids.
 * @internal
 */
final class RamseyUuid4Generator implements TraceIdGeneratorInterface
{
    public function __construct(private readonly UuidFactoryInterface $factory = new UuidFactory())
    {
    }

    public static function isSupported(): bool
    {
        return class_exists(UuidFactory::class);
    }

    public function generateTransactionId(): string
    {
        return (string)$this->factory->uuid4();
    }

    public function generateTraceId(): string
    {
        return (string)$this->factory->uuid4();
    }
}
