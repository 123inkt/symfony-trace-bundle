<?php

declare(strict_types=1);

namespace DR\SymfonyRequestId\DependencyInjection;

use DR\SymfonyRequestId\EventListener\RequestIdListener;
use DR\SymfonyRequestId\Generator\RamseyUuid4Generator;
use DR\SymfonyRequestId\Monolog\RequestIdProcessor;
use DR\SymfonyRequestId\RequestIdGenerator;
use DR\SymfonyRequestId\RequestIdStorage;
use DR\SymfonyRequestId\SimpleIdStorage;
use DR\SymfonyRequestId\Twig\RequestIdExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

/**
 * Registers some container configuration with the application.
 * @internal
 */
final class SymfonyRequestIdExtension extends ConfigurableExtension
{
    /**
     * @param array{
     *     request_header: string,
     *     trust_request_header: bool,
     *     response_header: string,
     *     storage_service: ?string,
     *     generator_service: ?string,
     *     enable_monolog: bool,
     *     enable_console: bool,
     *     enable_twig: bool,
     * } $mergedConfig
     */
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        $container->register(SimpleIdStorage::class)->setPublic(false);
        $container->register(RamseyUuid4Generator::class)->setPublic(false);

        $storeId = $mergedConfig['storage_service'] ?? SimpleIdStorage::class;
        $genId   = $mergedConfig['generator_service'] ?? RamseyUuid4Generator::class;

        $container->setAlias(RequestIdStorage::class, $storeId)->setPublic(true);
        $container->setAlias(RequestIdGenerator::class, $genId)->setPublic(true);

        $container->register(RequestIdListener::class)
            ->setArguments(
                [
                    $mergedConfig['request_header'],
                    $mergedConfig['response_header'],
                    $mergedConfig['trust_request_header'],
                    new Reference($storeId),
                    new Reference($genId),
                ]
            )
            ->setPublic(false)
            ->addTag('kernel.event_subscriber');

        if ($mergedConfig['enable_monolog']) {
            $container->register(RequestIdProcessor::class)
                ->addArgument(new Reference($storeId))
                ->setPublic(false)
                ->addTag('monolog.processor');
        }

        if (class_exists('Twig\Extension\AbstractExtension') && $mergedConfig['enable_twig']) {
            $container->register(RequestIdExtension::class)
                ->addArgument(new Reference($storeId))
                ->setPublic(false)
                ->addTag('twig.extension');
        }
    }
}
