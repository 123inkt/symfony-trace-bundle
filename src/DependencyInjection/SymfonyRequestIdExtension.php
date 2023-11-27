<?php

declare(strict_types=1);

namespace DR\SymfonyRequestId\DependencyInjection;

use Ramsey\Uuid\UuidFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use DR\SymfonyRequestId\SimpleIdStorage;
use DR\SymfonyRequestId\RequestIdStorage;
use DR\SymfonyRequestId\RequestIdGenerator;
use DR\SymfonyRequestId\Generator\RamseyUuid4Generator;
use DR\SymfonyRequestId\EventListener\RequestIdListener;
use DR\SymfonyRequestId\Monolog\RequestIdProcessor;
use DR\SymfonyRequestId\Twig\RequestIdExtension;

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
     * } $config
     */
    protected function loadInternal(array $config, ContainerBuilder $container) : void
    {
        $container->register(SimpleIdStorage::class)
            ->setPublic(false);
        $container->register(RamseyUuid4Generator::class)
            ->setPublic(false);

        $storeId = empty($config['storage_service']) ? SimpleIdStorage::class : $config['storage_service'];
        $genId = empty($config['generator_service']) ? RamseyUuid4Generator::class : $config['generator_service'];

        $container->setAlias(RequestIdStorage::class, $storeId)
            ->setPublic(true);
        $container->setAlias(RequestIdGenerator::class, $genId)
            ->setPublic(true);

        $container->register(RequestIdListener::class)
            ->setArguments([
                $config['request_header'],
                $config['response_header'],
                $config['trust_request_header'],
                new Reference($storeId),
                new Reference($genId),
            ])
            ->setPublic(false)
            ->addTag('kernel.event_subscriber');

        if (!empty($config['enable_monolog'])) {
            $container->register(RequestIdProcessor::class)
                ->addArgument(new Reference($storeId))
                ->setPublic(false)
                ->addTag('monolog.processor');
        }

        if (class_exists('Twig\Extension\AbstractExtension') && !empty($config['enable_twig'])) {
            $container->register(RequestIdExtension::class)
                ->addArgument(new Reference($storeId))
                ->setPublic(false)
                ->addTag('twig.extension');
        }
    }
}
