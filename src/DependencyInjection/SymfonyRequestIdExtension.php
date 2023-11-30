<?php

declare(strict_types=1);

namespace DR\SymfonyRequestId\DependencyInjection;

use DR\SymfonyRequestId\EventSubscriber\CommandSubscriber;
use DR\SymfonyRequestId\EventSubscriber\MessageBusSubscriber;
use DR\SymfonyRequestId\EventSubscriber\RequestIdSubscriber;
use DR\SymfonyRequestId\Generator\RamseyUuid4Generator;
use DR\SymfonyRequestId\Generator\SymfonyUuid4Generator;
use DR\SymfonyRequestId\Messenger\AppendRequestIdMiddleware;
use DR\SymfonyRequestId\Messenger\ApplyRequestIdMiddleware;
use DR\SymfonyRequestId\Monolog\RequestIdProcessor;
use DR\SymfonyRequestId\RequestIdGenerator;
use DR\SymfonyRequestId\RequestIdStorage;
use DR\SymfonyRequestId\SimpleIdStorage;
use DR\SymfonyRequestId\Twig\RequestIdExtension;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @codeCoverageIgnore - This is a configuration class, tested by the acceptance test
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
     *     enable_messenger: bool,
     *     enable_twig: bool,
     * } $mergedConfig
     */
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        $container->register(SimpleIdStorage::class)->setPublic(false);
        $storeId = $mergedConfig['storage_service'] ?? SimpleIdStorage::class;

        // configure generator service
        if (isset($mergedConfig['generator_service'])) {
            $generatorId = $mergedConfig['generator_service'];
        } elseif (RamseyUuid4Generator::isSupported()) {
            $generatorId = RamseyUuid4Generator::class;
        } elseif (SymfonyUuid4Generator::isSupported()) {
            $generatorId = SymfonyUuid4Generator::class;
        } else {
            throw new RuntimeException('No generator service found. Please install symfony/uid or ramsey/uuid');
        }

        if ($generatorId === RamseyUuid4Generator::class) {
            $container->register(RamseyUuid4Generator::class)->setPublic(false);
        } elseif ($generatorId === SymfonyUuid4Generator::class) {
            $container->register(SymfonyUuid4Generator::class)->setPublic(false);
        }

        $container->setAlias(RequestIdStorage::class, $storeId)->setPublic(true);
        $container->setAlias(RequestIdGenerator::class, $generatorId)->setPublic(true);

        $container->register(RequestIdSubscriber::class)
            ->setArguments(
                [
                    $mergedConfig['request_header'],
                    $mergedConfig['response_header'],
                    $mergedConfig['trust_request_header'],
                    new Reference($storeId),
                    new Reference($generatorId),
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

        if (class_exists('Symfony\Component\Console\Application') && $mergedConfig['enable_console']) {
            $container->register(CommandSubscriber::class)
                ->setArguments([new Reference($storeId), new Reference($generatorId)])
                ->setPublic(false)
                ->addTag('kernel.event_subscriber');
        }

        if ($mergedConfig['enable_messenger']) {
            if (interface_exists(MessageBusInterface::class) === false) {
                throw new LogicException(
                    'Messenger support cannot be enabled as the Messenger component is not installed. ' .
                    'Try running "composer require symfony/messenger".'
                );
            }
            $container->register(MessageBusSubscriber::class)
                ->setArguments([new Reference($storeId)])
                ->setPublic(false)
                ->addTag('kernel.event_subscriber');
        }

        if (class_exists('Twig\Extension\AbstractExtension') && $mergedConfig['enable_twig']) {
            $container->register(RequestIdExtension::class)
                ->addArgument(new Reference($storeId))
                ->setPublic(false)
                ->addTag('twig.extension');
        }
    }
}
