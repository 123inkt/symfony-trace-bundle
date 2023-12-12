<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Messenger;

use DR\SymfonyTraceBundle\TraceContext;
use Symfony\Component\Messenger\Stamp\StampInterface;

final class TraceStamp implements StampInterface
{
    /**
     * @codeCoverageIgnore - Simple DTO
     */
    public function __construct(public readonly TraceContext $trace)
    {
    }
}
