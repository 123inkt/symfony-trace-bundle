<?php

declare(strict_types=1);

namespace DR\SymfonyRequestId\DependencyInjection\Compiler;

use DR\SymfonyRequestId\Http\RequestIdAwareHttpClient;
use DR\SymfonyRequestId\RequestIdStorage;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @codeCoverageIgnore - This is a config class
 */
class HttpClientRequestIdPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('http_client')) {
            $container->getDefinition('http_client')
                ->addTag('http_client.request_id');
        }

        $taggedServices = $container->findTaggedServiceIds('http_client.request_id');

        foreach ($taggedServices as $id => $tag) {
            $container->register($id . '.request_id', RequestIdAwareHttpClient::class)
                ->setArguments([new Reference($id . '.request_id' . '.inner'), new Reference(RequestIdStorage::class)])
                ->setDecoratedService($id, null, 10);
        }
    }
}
