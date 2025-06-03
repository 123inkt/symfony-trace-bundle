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
use DR\SymfonyTraceBundle\Sentry\SentryAwareTraceStorage;
use DR\SymfonyTraceBundle\Service\TraceContext\TraceContextService;
use DR\SymfonyTraceBundle\Service\TraceId\TraceIdService;
use DR\SymfonyTraceBundle\Service\TraceServiceInterface;
use DR\SymfonyTraceBundle\TraceStorage;
use DR\SymfonyTraceBundle\TraceStorageInterface;
use DR\SymfonyTraceBundle\Twig\TraceExtension;
use RuntimeException;
use Sentry\State\HubInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Twig\Extension\AbstractExtension;

/**
 * @phpstan-type Options array{
 *      traceMode: 'tracecontext'|'traceid',
 *      traceid: array{
 *          request_header: string,
 *          response_header: string,
 *          generator_service: ?string,
 *      },
 *      request: array{
 *          trust_header: bool,
 *          trusted_ips: string[]|string|null,
 *      },
 *      response: array{
 *          send_header: bool,
 *          trusted_ips: string[]|string|null,
 *      },
 *      storage_service: ?string,
 *      monolog: array{
 *          enabled: bool|null,
 *      },
 *      console: array{
 *          enabled: bool,
 *          trace_id: ?string
 *      },
 *      messenger: array{
 *          enabled: bool,
 *      },
 *      twig: array{
 *          enabled: bool,
 *      },
 *      http_client: array{
 *          enabled: bool,
 *          tag_default_client: bool,
 *          header: string
 *      },
 *      sentry: array{
 *          enabled: bool,
 *          hub_service: string
 *      }
 *  }
 * @codeCoverageIgnore - This is a configuration class, tested by the functional test
 * @internal
 */
final class SymfonyTraceExtension extends ConfigurableExtension
{
    public const PARAMETER_KEY = 'digital_revolution.symfony_trace';

    /**
     * @phpstan-param Options $mergedConfig
     */
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        $container->register(TraceStorage::class)->setPublic(false);

        $storeId     = $mergedConfig['storage_service'] ?? TraceStorage::class;
        $generatorId = $this->configureGeneratorId($mergedConfig, $container);
        $serviceId   = $this->configureTraceServiceId($mergedConfig, $container, $generatorId);

        $container->setAlias(TraceServiceInterface::class, $serviceId)->setPublic(false);
        $container->setAlias(TraceStorageInterface::class, $storeId)->setPublic(true);
        $container->setAlias(TraceIdGeneratorInterface::class, $generatorId)->setPublic(true);

        $container->register(TraceSubscriber::class)
            ->setArguments(
                [
                    $mergedConfig['request']['trust_header'],
                    $mergedConfig['request']['trusted_ips'],
                    $mergedConfig['response']['send_header'],
                    $mergedConfig['response']['trusted_ips'],
                    new Reference(TraceServiceInterface::class),
                    new Reference(TraceStorageInterface::class)
                ]
            )
            ->setPublic(false)
            ->addTag('kernel.event_subscriber');

        $this->configureMonolog($mergedConfig, $container);
        $this->configureConsole($mergedConfig, $container);
        $this->configureMessenger($mergedConfig, $container);
        $this->configureTwig($mergedConfig, $container);
        $this->configureHttpClient($mergedConfig, $container);
        $this->configureSentry($mergedConfig, $container);
    }

    /**
     * @phpstan-param Options $mergedConfig
     */
    private function configureGeneratorId(array $mergedConfig, ContainerBuilder $container): string
    {
        // configure generator service
        if ($mergedConfig['traceMode'] === Configuration::TRACEMODE_TRACECONTEXT) {
            $generatorId = TraceContextIdGenerator::class;
            $container->register(TraceContextIdGenerator::class)->setPublic(false);
        } elseif (isset($mergedConfig['traceid']['generator_service'])) {
            $generatorId = $mergedConfig['traceid']['generator_service'];
        } elseif (RamseyUuid4Generator::isSupported()) {
            $generatorId = RamseyUuid4Generator::class;
            $container->register(RamseyUuid4Generator::class)->setPublic(false);
        } elseif (SymfonyUuid4Generator::isSupported()) {
            $generatorId = SymfonyUuid4Generator::class;
            $container->register(SymfonyUuid4Generator::class)->setPublic(false);
        } else {
            throw new RuntimeException('No generator service found. Please install symfony/uid or ramsey/uuid');
        }

        return $generatorId;
    }

    /**
     * @phpstan-param Options $mergedConfig
     */
    private function configureTraceServiceId(array $mergedConfig, ContainerBuilder $container, string $generatorId): string
    {
        if ($mergedConfig['traceMode'] === Configuration::TRACEMODE_TRACECONTEXT) {
            $container->register(TraceContextService::class)
                ->setArguments([new Reference(TraceContextIdGenerator::class)])
                ->setPublic(false);

            return TraceContextService::class;
        }

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

        return TraceIdService::class;
    }

    /**
     * @phpstan-param Options $mergedConfig
     */
    private function configureMonolog(array $mergedConfig, ContainerBuilder $container): void
    {
        if ($mergedConfig['monolog']['enabled'] === false) {
            return;
        }

        $container->register(TraceProcessor::class)
            ->addArgument(new Reference(TraceStorageInterface::class))
            ->setPublic(false)
            ->addTag('monolog.processor');
    }

    /**
     * @phpstan-param Options $mergedConfig
     */
    private function configureConsole(array $mergedConfig, ContainerBuilder $container): void
    {
        $enabled = $mergedConfig['enable_console'] ?? $mergedConfig['console']['enabled'];
        if (class_exists(Application::class) === false || $enabled === false) {
            return;
        }

        $container->register(CommandSubscriber::class)
            ->setArguments(
                [
                    new Reference(TraceStorageInterface::class),
                    new Reference(TraceServiceInterface::class),
                    $mergedConfig['console']['trace_id']
                ]
            )
            ->setPublic(false)
            ->addTag('kernel.event_subscriber');
    }

    /**
     * @phpstan-param Options $mergedConfig
     */
    private function configureMessenger(array $mergedConfig, ContainerBuilder $container): void
    {
        if ($mergedConfig['messenger']['enabled'] === false) {
            return;
        }
        if (interface_exists(MessageBusInterface::class) === false) {
            throw new LogicException(
                'Messenger support cannot be enabled as the Messenger component is not installed. ' .
                'Try running "composer require symfony/messenger".'
            );
        }
        $container->register(MessageBusSubscriber::class)
            ->setArguments([new Reference(TraceStorageInterface::class), new Reference(TraceIdGeneratorInterface::class)])
            ->setPublic(false)
            ->addTag('kernel.event_subscriber');
    }

    /**
     * @phpstan-param Options $mergedConfig
     */
    private function configureTwig(array $mergedConfig, ContainerBuilder $container): void
    {
        if (class_exists(AbstractExtension::class) === false || $mergedConfig['twig']['enabled'] === false) {
            return;
        }

        $container->register(TraceExtension::class)
            ->addArgument(new Reference(TraceStorageInterface::class))
            ->setPublic(false)
            ->addTag('twig.extension');
    }

    /**
     * @phpstan-param Options $mergedConfig
     */
    private function configureHttpClient(array $mergedConfig, ContainerBuilder $container): void
    {
        $container->setParameter(self::PARAMETER_KEY . '.http_client.enabled', $mergedConfig['http_client']['enabled']);
        if ($mergedConfig['http_client']['enabled'] === false) {
            return;
        }
        if (interface_exists(HttpClientInterface::class) === false) {
            throw new LogicException(
                'HttpClient support cannot be enabled as the HttpClient component is not installed. ' .
                'Try running "composer require symfony/http-client".'
            );
        }

        $container->setParameter(self::PARAMETER_KEY . '.http_client.tag_default_client', $mergedConfig['http_client']['tag_default_client']);
        $container->setParameter(self::PARAMETER_KEY . '.http_client.header', $mergedConfig['http_client']['header']);
    }

    /**
     * @phpstan-param Options $mergedConfig
     */
    private function configureSentry(array $mergedConfig, ContainerBuilder $container): void
    {
        if ($mergedConfig['sentry']['enabled'] === false) {
            return;
        }
        if (interface_exists(HubInterface::class) === false) {
            throw new LogicException('Sentry support cannot be enabled as Sentry is not installed. Try running "composer require sentry/sentry".');
        }

        $storeId = TraceStorageInterface::class;

        $container->register($storeId . '.sentry_aware_trace_storage', SentryAwareTraceStorage::class)
            ->setArguments(
                [
                    new Reference($storeId . '.sentry_aware_trace_storage' . '.inner'),
                    new Reference($mergedConfig['sentry']['hub_service']),
                ]
            )
            ->setDecoratedService($storeId, null, 1);
    }
}
