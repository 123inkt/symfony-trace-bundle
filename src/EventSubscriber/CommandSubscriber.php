<?php
declare(strict_types=1);

namespace DR\SymfonyTraceBundle\EventSubscriber;

use DR\SymfonyTraceBundle\IdGeneratorInterface;
use DR\SymfonyTraceBundle\IdStorageInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Set up tracing ids for command.
 * @internal
 */
final class CommandSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly IdStorageInterface $idStorage, private readonly IdGeneratorInterface $generator)
    {
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
        $this->idStorage->setTraceId($this->generator->generate());
        $this->idStorage->setTransactionId($this->generator->generate());
    }
}
