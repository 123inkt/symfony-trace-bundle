<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\DependencyInjection\Compiler;

use DR\SymfonyTraceBundle\DependencyInjection\SymfonyTraceExtension;
use DR\SymfonyTraceBundle\Http\TraceAwareHttpClient;
use DR\SymfonyTraceBundle\Sentry\SentryAwareTraceStorage;
use DR\SymfonyTraceBundle\Service\TraceServiceInterface;
use DR\SymfonyTraceBundle\TraceStorageInterface;
use DR\Utils\Assert;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @codeCoverageIgnore - This is a config class
 */
class SentryTracePass implements CompilerPassInterface
{
    /**
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function process(ContainerBuilder $container): void
    {
        if ($container->hasParameter(SymfonyTraceExtension::PARAMETER_KEY . '.sentry.enabled') === false ||
            $container->getParameter(SymfonyTraceExtension::PARAMETER_KEY . '.sentry.enabled') === false
        ) {
            return;
        }

        $hubServiceId = Assert::string($container->getParameter(SymfonyTraceExtension::PARAMETER_KEY . '.sentry.service_id'));

        $container->register(TraceStorageInterface::class . '.trace_id', SentryAwareTraceStorage::class)
            ->setArguments([
                new Reference(TraceStorageInterface::class . '.trace_id' . '.inner'),
                new Reference($hubServiceId),
            ])
            ->setDecoratedService(TraceStorageInterface::class, null, 1);
    }
}
