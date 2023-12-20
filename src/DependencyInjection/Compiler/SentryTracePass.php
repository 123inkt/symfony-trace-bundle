<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\DependencyInjection\Compiler;

use DR\SymfonyTraceBundle\DependencyInjection\SymfonyTraceExtension;
use DR\SymfonyTraceBundle\Sentry\SentryAwareTraceStorage;
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

        $storeId      = Assert::string($container->getParameter(SymfonyTraceExtension::PARAMETER_KEY . '.sentry.store_id'));
        $hubServiceId = Assert::string($container->getParameter(SymfonyTraceExtension::PARAMETER_KEY . '.sentry.service_id'));

        $container->register($storeId . '.sentry_aware_trace_storage', SentryAwareTraceStorage::class)
            ->setArguments(
                [
                    new Reference($storeId . '.sentry_aware_trace_storage' . '.inner'),
                    new Reference($hubServiceId),
                ]
            )
            ->setDecoratedService($storeId, null, 1);
    }
}
