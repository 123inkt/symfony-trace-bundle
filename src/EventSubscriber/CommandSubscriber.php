<?php
declare(strict_types=1);

namespace DR\SymfonyRequestId\EventSubscriber;

use DR\SymfonyRequestId\RequestIdGenerator;
use DR\SymfonyRequestId\RequestIdStorage;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Set up request id for command.
 * @internal
 */
final class CommandSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly RequestIdStorage $requestIdStorage, private readonly RequestIdGenerator $generator)
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
        $this->requestIdStorage->setRequestId($this->generator->generate());
    }
}
