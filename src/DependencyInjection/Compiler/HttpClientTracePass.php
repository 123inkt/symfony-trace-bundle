<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\DependencyInjection\Compiler;

use DR\SymfonyTraceBundle\DependencyInjection\SymfonyTraceExtension;
use DR\SymfonyTraceBundle\Http\TraceAwareHttpClient;
use DR\SymfonyTraceBundle\Service\TraceServiceInterface;
use DR\SymfonyTraceBundle\TraceStorageInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @codeCoverageIgnore - This is a config class
 */
class HttpClientTracePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->hasParameter(SymfonyTraceExtension::PARAMETER_KEY . '.http_client.enabled') === false ||
            $container->getParameter(SymfonyTraceExtension::PARAMETER_KEY . '.http_client.enabled') === false
        ) {
            return;
        }

        if ($container->getParameter(SymfonyTraceExtension::PARAMETER_KEY . '.http_client.tag_default_client') === true &&
            $container->hasDefinition('http_client')
        ) {
            $container->getDefinition('http_client')
                ->addTag('http_client.trace_id');
        }

        $taggedServices = $container->findTaggedServiceIds('http_client.trace_id');
        foreach ($taggedServices as $id => $tag) {
            $container->register($id . '.trace_id', TraceAwareHttpClient::class)
                ->setArguments([
                    new Reference($id . '.trace_id' . '.inner'),
                    new Reference(TraceStorageInterface::class),
                    new Reference(TraceServiceInterface::class),
                ])
                ->setDecoratedService($id, null, 1);
        }
    }
}
