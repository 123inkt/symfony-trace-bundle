<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\DependencyInjection\Compiler;

use DR\SymfonyTraceBundle\DependencyInjection\SymfonyTraceExtension;
use DR\SymfonyTraceBundle\Http\TraceContextAwareHttpClient;
use DR\SymfonyTraceBundle\Http\TraceIdAwareHttpClient;
use DR\SymfonyTraceBundle\Service\TraceContextService;
use DR\SymfonyTraceBundle\TraceContext;
use DR\SymfonyTraceBundle\TraceId;
use DR\SymfonyTraceBundle\TraceStorageInterface;
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
            if ($container->getParameter(SymfonyRequestIdExtension::PARAMETER_KEY . '.traceMode') === TraceId::TRACEMODE) {
                $container->register($id . '.trace_id', TraceIdAwareHttpClient::class)
                    ->setArguments([
                        new Reference($id . '.trace_id' . '.inner'),
                        new Reference(TraceStorageInterface::class),
                        new Parameter(SymfonyTraceExtension::PARAMETER_KEY . '.http_client.header'),
                    ])
                    ->setDecoratedService($id, null, 1);
            } else {
                $container->register($id . '.trace_id', TraceContextAwareHttpClient::class)
                    ->setArguments([
                        new Reference($id . '.trace_id' . '.inner'),
                        new Reference(TraceStorageInterface::class),
                        new Reference(TraceContextService::class)
                    ])
                    ->setDecoratedService($id, null, 1);
            }
        }
    }
}
