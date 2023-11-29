<?php
declare(strict_types=1);

namespace DR\SymfonyRequestId\DependencyInjection\CompilerPass;

use DR\SymfonyRequestId\Messenger\RequestIdMiddleware;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class MessengerCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->getParameter('digital-revolution.symfony-request-id.messenger.enabled') !== true) {
            return;
        };

        $buses = $container->getParameter('digital-revolution.symfony-request-id.messenger.buses');

        $bus        = $container->getDefinition('messenger.bus.default');
        $middleware = $container->getParameter('messenger.bus.default.middleware');

        $middleware[] = ['id' => RequestIdMiddleware::class, 'arguments' => ['messenger.bus.default']];
        $container->setParameter('messenger.bus.default.middleware', $middleware);

        $test       = true;
    }
}
