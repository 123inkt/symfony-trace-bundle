<?php

declare(strict_types=1);

namespace DR\SymfonyRequestId\DependencyInjection;

use DR\SymfonyRequestId\EventSubscriber\CommandSubscriber;
use DR\SymfonyRequestId\EventSubscriber\MessageBusSubscriber;
use DR\SymfonyRequestId\EventSubscriber\TraceContextSubscriber;
use DR\SymfonyRequestId\EventSubscriber\TraceIdSubscriber;
use DR\SymfonyRequestId\Generator\TraceId\TraceIdGeneratorInterface;
use DR\SymfonyRequestId\Generator\TraceId\RamseyUuid4Generator;
use DR\SymfonyRequestId\Generator\TraceId\SymfonyUuid4Generator;
use DR\SymfonyRequestId\Generator\TraceContext\TraceContextIdGenerator;
use DR\SymfonyRequestId\Service\TraceContextService;
use DR\SymfonyRequestId\TraceId;
use DR\SymfonyRequestId\TraceStorageInterface;
use DR\SymfonyRequestId\Monolog\TraceIdProcessor;
use DR\SymfonyRequestId\TraceStorage;
use DR\SymfonyRequestId\Twig\TraceIdExtension;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Twig\Extension\AbstractExtension;
use Symfony\Component\Console\Application;

/**
 * @codeCoverageIgnore - This is a configuration class, tested by the functional test
 * @internal
 */
final class SymfonyRequestIdExtension extends ConfigurableExtension
{
    public const PARAMETER_KEY = 'digital_revolution.symfony_request_id';

    /**
     * @param array{
     *     traceMode: 'tracecontext'|'traceid',
     *     traceid: array{
     *         request_header: string,
     *         response_header: string,
     *         generator_service: ?string,
     *     },
     *     trust_request_header: bool,
     *     storage_service: ?string,
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
        $container->register(TraceStorage::class)->setPublic(false);
        $storeId = $mergedConfig['storage_service'] ?? TraceStorage::class;

        // configure generator service
        if (isset($mergedConfig['traceid']['generator_service'])) {
            $generatorId = $mergedConfig['traceid']['generator_service'];
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

        $container->register(TraceContextService::class)->setPublic(false);
        $container->register(TraceContextIdGenerator::class)->setPublic(false);

        $container->setAlias(TraceStorageInterface::class, $storeId)->setPublic(true);
        $container->setAlias(TraceIdGeneratorInterface::class, $generatorId)->setPublic(true);

        if ($mergedConfig['traceMode'] === TraceId::TRACEMODE) {
            $container->register(TraceIdSubscriber::class)
                ->setArguments(
                    [
                        $mergedConfig[TraceId::TRACEMODE]['request_header'],
                        $mergedConfig[TraceId::TRACEMODE]['response_header'],
                        $mergedConfig['trust_request_header'],
                        new Reference($storeId),
                        new Reference($generatorId),
                    ]
                )
                ->setPublic(false)
                ->addTag('kernel.event_subscriber');
        } else {
            $container->register(TraceContextSubscriber::class)
                ->setArguments(
                    [
                        $mergedConfig['trust_request_header'],
                        new Reference(TraceContextService::class),
                        new Reference($storeId),
                        new Reference(TraceContextIdGenerator::class),
                    ]
                )
                ->setPublic(false)
                ->addTag('kernel.event_subscriber');
        }

        if ($mergedConfig['enable_monolog']) {
            $container->register(TraceIdProcessor::class)
                ->addArgument(new Reference($storeId))
                ->setPublic(false)
                ->addTag('monolog.processor');
        }

        if (class_exists(Application::class) && $mergedConfig['enable_console']) {
            $container->register(CommandSubscriber::class)
                ->setArguments(
                    [
                        $mergedConfig['traceMode'],
                        new Reference($storeId),
                        new Reference($generatorId),
                        new Reference(TraceContextIdGenerator::class)
                    ]
                )
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
                ->setArguments(
                    [
                        $mergedConfig['traceMode'],
                        new Reference($storeId),
                        new Reference($generatorId),
                        new Reference(TraceContextIdGenerator::class)
                    ]
                )
                ->setPublic(false)
                ->addTag('kernel.event_subscriber');
        }

        if (class_exists(AbstractExtension::class) && $mergedConfig['enable_twig']) {
            $container->register(TraceIdExtension::class)
                ->addArgument(new Reference($storeId))
                ->setPublic(false)
                ->addTag('twig.extension');
        }

        $container->setParameter(self::PARAMETER_KEY . '.traceMode', $mergedConfig['traceMode']);
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
