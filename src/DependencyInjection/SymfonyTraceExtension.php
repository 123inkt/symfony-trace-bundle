<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\DependencyInjection;

use DR\SymfonyTraceBundle\EventSubscriber\CommandSubscriber;
use DR\SymfonyTraceBundle\EventSubscriber\MessageBusSubscriber;
use DR\SymfonyTraceBundle\EventSubscriber\TraceIdSubscriber;
use DR\SymfonyTraceBundle\Generator\RamseyUuid4Generator;
use DR\SymfonyTraceBundle\Generator\SymfonyUuid4Generator;
use DR\SymfonyTraceBundle\Monolog\TraceIdProcessor;
use DR\SymfonyTraceBundle\IdGeneratorInterface;
use DR\SymfonyTraceBundle\IdStorageInterface;
use DR\SymfonyTraceBundle\SimpleIdStorage;
use DR\SymfonyTraceBundle\Twig\TraceIdExtension;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @codeCoverageIgnore - This is a configuration class, tested by the functional test
 * @internal
 */
final class SymfonyTraceExtension extends ConfigurableExtension
{
    public const PARAMETER_KEY = 'digital_revolution.symfony_trace';

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
     *     http_client: array{
     *         enabled: bool,
     *         tag_default_client: bool,
     *         header: string
     *     }
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

        $container->setAlias(IdStorageInterface::class, $storeId)->setPublic(true);
        $container->setAlias(IdGeneratorInterface::class, $generatorId)->setPublic(true);

        $container->register(TraceIdSubscriber::class)
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
            $container->register(TraceIdProcessor::class)
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
                ->setArguments([new Reference($storeId), new Reference($generatorId)])
                ->setPublic(false)
                ->addTag('kernel.event_subscriber');
        }

        if (class_exists('Twig\Extension\AbstractExtension') && $mergedConfig['enable_twig']) {
            $container->register(TraceIdExtension::class)
                ->addArgument(new Reference($storeId))
                ->setPublic(false)
                ->addTag('twig.extension');
        }

        $container->setParameter(self::PARAMETER_KEY . '.http_client.enabled', false);
        if (isset($mergedConfig['http_client']) && $mergedConfig['http_client']['enabled']) {
            if (interface_exists(HttpClientInterface::class) === false) {
                throw new LogicException(
                    'HttpClient support cannot be enabled as the HttpClient component is not installed. ' .
                    'Try running "composer require symfony/http-client".'
                );
            }

            $container->setParameter(self::PARAMETER_KEY . '.http_client.enabled', $mergedConfig['http_client']['enabled']);
            $container->setParameter(self::PARAMETER_KEY . '.http_client.tag_default_client', $mergedConfig['http_client']['tag_default_client']);
            $container->setParameter(self::PARAMETER_KEY . '.http_client.header', $mergedConfig['http_client']['header']);
        }
    }
}
