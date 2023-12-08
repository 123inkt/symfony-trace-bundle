<?php

declare(strict_types=1);

namespace DR\SymfonyRequestId\DependencyInjection\Compiler;

use DR\SymfonyRequestId\DependencyInjection\SymfonyRequestIdExtension;
use DR\SymfonyRequestId\Http\TraceIdAwareHttpClient;
use DR\SymfonyRequestId\IdStorageInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @codeCoverageIgnore - This is a config class
 */
class HttpClientTraceIdPass implements CompilerPassInterface
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

        if ($container->getParameter(SymfonyRequestIdExtension::PARAMETER_KEY . '.http_client.tag_default_client') === true &&
            $container->hasDefinition('http_client')
        ) {
            $container->getDefinition('http_client')
                ->addTag('http_client.trace_id');
        }

        $taggedServices = $container->findTaggedServiceIds('http_client.trace_id');

        foreach ($taggedServices as $id => $tag) {
            $container->register($id . '.trace_id', TraceIdAwareHttpClient::class)
                ->setArguments([
                    new Reference($id . '.trace_id' . '.inner'),
                    new Reference(IdStorageInterface::class),
                    new Parameter(SymfonyRequestIdExtension::PARAMETER_KEY . '.http_client.header')
                ])
                ->setDecoratedService($id, null, 1);
        }
    }
}
