<?php
declare(strict_types=1);

namespace DR\SymfonyRequestId\EventSubscriber;

use DR\SymfonyRequestId\Generator\TraceId\TraceIdGeneratorInterface;
use DR\SymfonyRequestId\Generator\TraceContext\TraceContextIdGenerator;
use DR\SymfonyRequestId\TraceId;
use DR\SymfonyRequestId\TraceContext;
use DR\SymfonyRequestId\TraceStorageInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Set up tracing ids for command.
 * @internal
 */
final class CommandSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly string                    $traceMode,
        private readonly TraceStorageInterface     $traceStorage,
        private readonly TraceIdGeneratorInterface $generator,
        private readonly TraceContextIdGenerator   $traceContextIdGenerator
    ) {
    }

    /**
     * @return array<string, array<int, int|string>>
     */
    public static function getSubscribedEvents(): array
    {
        return [ConsoleEvents::COMMAND => ['onCommand', 999]];
    }

    public function onCommand(): void
    {
        if ($this->traceMode === TraceId::TRACEMODE) {
            $traceId = new TraceId();
            $traceId->setTraceId($this->generator->generate());
            $traceId->setTransactionId($this->generator->generate());

            $this->traceStorage->setTrace($traceId);
        } else {
            $traceContext = new TraceContext();
            $traceContext->setTraceId($this->traceContextIdGenerator->generateTraceId());
            $traceContext->setTransactionId($this->traceContextIdGenerator->generateTransactionId());

            $this->traceStorage->setTrace($traceContext);
        }
    }
}
