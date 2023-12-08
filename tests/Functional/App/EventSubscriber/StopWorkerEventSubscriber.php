<?php
declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Tests\Functional\App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;

class StopWorkerEventSubscriber implements EventSubscriberInterface
{
    public function onWorkerRunning(WorkerRunningEvent $event): void
    {
        $event->getWorker()->stop();
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [WorkerRunningEvent::class => 'onWorkerRunning'];
    }
}
