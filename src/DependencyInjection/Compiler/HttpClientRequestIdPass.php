<?php

declare(strict_types=1);

namespace DR\SymfonyRequestId\DependencyInjection\Compiler;

use DR\SymfonyRequestId\DependencyInjection\SymfonyRequestIdExtension;
use DR\SymfonyRequestId\Http\RequestIdAwareHttpClient;
use DR\SymfonyRequestId\RequestIdStorageInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @codeCoverageIgnore - This is a config class
 */
class HttpClientRequestIdPass implements CompilerPassInterface
{
    /**
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasParameter(SymfonyRequestIdExtension::PARAMETER_KEY . '.http_client.enabled') === false ||
            $container->getParameter(SymfonyRequestIdExtension::PARAMETER_KEY . '.http_client.enabled') === false
        ) {
            return;
        }

        if ($container->getParameter('digital_revolution.symfony_request_id.http_client.tag_default_client') === true &&
            $container->hasDefinition('http_client')
        ) {
            $container->getDefinition('http_client')
                ->addTag('http_client.request_id');
        }

        $taggedServices = $container->findTaggedServiceIds('http_client.request_id');

        foreach ($taggedServices as $id => $tag) {
            $container->register($id . '.request_id', RequestIdAwareHttpClient::class)
                ->setArguments([
                    new Reference($id . '.request_id' . '.inner'),
                    new Reference(RequestIdStorageInterface::class),
                    new Parameter('digital_revolution.symfony_request_id.http_client.header')
                ])
                ->setDecoratedService($id, null, 1);
        }
    }
}
