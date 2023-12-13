<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\DependencyInjection;

use DR\SymfonyTraceBundle\EventSubscriber\CommandSubscriber;
use DR\SymfonyTraceBundle\EventSubscriber\MessageBusSubscriber;
use DR\SymfonyTraceBundle\EventSubscriber\TraceSubscriber;
use DR\SymfonyTraceBundle\Generator\TraceContext\TraceContextIdGenerator;
use DR\SymfonyTraceBundle\Generator\TraceId\RamseyUuid4Generator;
use DR\SymfonyTraceBundle\Generator\TraceId\SymfonyUuid4Generator;
use DR\SymfonyTraceBundle\Generator\TraceIdGeneratorInterface;
use DR\SymfonyTraceBundle\Monolog\TraceProcessor;
use DR\SymfonyTraceBundle\Service\TraceContext\TraceContextService;
use DR\SymfonyTraceBundle\Service\TraceId\TraceIdService;
use DR\SymfonyTraceBundle\Service\TraceServiceInterface;
use DR\SymfonyTraceBundle\TraceStorage;
use DR\SymfonyTraceBundle\TraceStorageInterface;
use DR\SymfonyTraceBundle\Twig\TraceExtension;
use RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Twig\Extension\AbstractExtension;

/**
 * @codeCoverageIgnore - This is a configuration class, tested by the functional test
 * @internal
 */
final class SymfonyTraceExtension extends ConfigurableExtension
{
    public const PARAMETER_KEY = 'digital_revolution.symfony_trace';

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
        if ($mergedConfig['traceMode'] === Configuration::TRACEMODE_TRACECONTEXT) {
            $generatorId = TraceContextIdGenerator::class;
        } elseif (isset($mergedConfig['traceid']['generator_service'])) {
            $generatorId = $mergedConfig['traceid']['generator_service'];
        } elseif (RamseyUuid4Generator::isSupported()) {
            $generatorId = RamseyUuid4Generator::class;
        } elseif (SymfonyUuid4Generator::isSupported()) {
            $generatorId = SymfonyUuid4Generator::class;
        } else {
            throw new RuntimeException('No generator service found. Please install symfony/uid or ramsey/uuid');
        }

        $container->register(TraceContextIdGenerator::class)->setPublic(false);
        if ($generatorId === RamseyUuid4Generator::class) {
            $container->register(RamseyUuid4Generator::class)->setPublic(false);
        } elseif ($generatorId === SymfonyUuid4Generator::class) {
            $container->register(SymfonyUuid4Generator::class)->setPublic(false);
        }

        if ($mergedConfig['traceMode'] === Configuration::TRACEMODE_TRACEID) {
            $serviceId = TraceIdService::class;
        } else {
            $serviceId = TraceContextService::class;
        }

        $container->setAlias(TraceServiceInterface::class, $serviceId)->setPublic(false);
        $container->register(TraceContextService::class)
            ->setArguments([new Reference(TraceContextIdGenerator::class)])
            ->setPublic(false);

        $container->register(TraceIdService::class)
            ->setArguments(
                [
                    $mergedConfig['traceid']['request_header'],
                    $mergedConfig['traceid']['response_header'],
                    $mergedConfig['http_client']['header'] ?? $mergedConfig['traceid']['response_header'],
                    new Reference($generatorId)
                ]
            )
            ->setPublic(false);

        $container->setAlias(TraceStorageInterface::class, $storeId)->setPublic(true);
        $container->setAlias(TraceIdGeneratorInterface::class, $generatorId)->setPublic(true);

        $container->register(TraceSubscriber::class)
            ->setArguments(
                [
                    $mergedConfig['trust_request_header'],
                    new Reference($serviceId),
                    new Reference($storeId)
                ]
            )
            ->setPublic(false)
            ->addTag('kernel.event_subscriber');

        if ($mergedConfig['enable_monolog']) {
            $container->register(TraceProcessor::class)
                ->addArgument(new Reference($storeId))
                ->setPublic(false)
                ->addTag('monolog.processor');
        }

        if (class_exists(Application::class) && $mergedConfig['enable_console']) {
            $container->register(CommandSubscriber::class)
                ->setArguments([new Reference($storeId), new Reference($serviceId)])
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

        if (class_exists(AbstractExtension::class) && $mergedConfig['enable_twig']) {
            $container->register(TraceExtension::class)
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
