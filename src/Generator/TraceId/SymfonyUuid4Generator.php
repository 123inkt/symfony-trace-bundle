<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Generator\TraceId;

use DR\SymfonyTraceBundle\Generator\TraceIdGeneratorInterface;
use Symfony\Component\Uid\Factory\UuidFactory;
use Symfony\Component\Uid\UuidV4;

/**
 * Uses symfony/uid to generate a UUIDv4 request ID.
 * @internal
 */
final class SymfonyUuid4Generator implements TraceIdGeneratorInterface
{
    public function __construct(private readonly UuidFactory $factory = new UuidFactory(UuidV4::class, UuidV4::class, UuidV4::class, UuidV4::class))
    {
    }

    public static function isSupported(): bool
    {
        return class_exists(UuidFactory::class);
    }

    public function generateTransactionId(): string
    {
        return (string)$this->factory->create();
    }

    public function generateTraceId(): string
    {
        return (string)$this->factory->create();
    }
}
