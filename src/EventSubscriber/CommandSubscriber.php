<?php
declare(strict_types=1);

namespace DR\SymfonyTraceBundle\EventSubscriber;

use DR\SymfonyTraceBundle\Service\TraceServiceInterface;
use DR\SymfonyTraceBundle\TraceStorageInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Set up tracing ids for command.
 * @internal
 */
final class CommandSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly TraceStorageInterface $storage,
        private readonly TraceServiceInterface $service,
        private readonly ?string $traceId,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [ConsoleEvents::COMMAND => ['onCommand', 999]];
    }

    public function onCommand(): void
    {
        // If the trace ID is already set by another process, don't overwrite it
        if ($this->storage->getTraceId() !== null) {
            return;
        }

        if ($this->traceId !== null && $this->traceId !== '') {
            $this->storage->setTrace($this->service->createTraceFrom($this->traceId));

            return;
        }

        $this->storage->setTrace($this->service->createNewTrace());
    }
}
