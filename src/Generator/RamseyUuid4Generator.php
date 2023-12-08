<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Generator;

use DR\SymfonyTraceBundle\IdGeneratorInterface;
use Ramsey\Uuid\UuidFactory;
use Ramsey\Uuid\UuidFactoryInterface;

/**
 * Uses `ramsey/uuid` to generator v4 UUIDs for request ids.
 */
final class RamseyUuid4Generator implements IdGeneratorInterface
{
    public function __construct(private readonly UuidFactoryInterface $factory = new UuidFactory())
    {
    }

    public static function isSupported(): bool
    {
        return class_exists(UuidFactory::class);
    }

    public function generate(): string
    {
        return (string)$this->factory->uuid4();
    }
}
