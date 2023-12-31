<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Twig;

use DR\SymfonyTraceBundle\TraceStorageInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Add trace_id() and transaction_id() to twig as a function.
 * @internal
 */
final class TraceExtension extends AbstractExtension
{
    public function __construct(private readonly TraceStorageInterface $storage)
    {
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('trace_id', [$this->storage, 'getTraceId']),
            new TwigFunction('transaction_id', [$this->storage, 'getTransactionId'])
        ];
    }
}
