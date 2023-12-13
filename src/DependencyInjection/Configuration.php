<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @codeCoverageIgnore - This is a configuration class, tested by the functional test
 * @internal
 */
class Configuration implements ConfigurationInterface
{
    public const TRACEMODE_TRACECONTEXT = 'tracecontext';
    public const TRACEMODE_TRACEID = 'traceid';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $tree = new TreeBuilder('symfony_trace');
        /** @var ArrayNodeDefinition $node */
        $node = $tree->getRootNode();
        $node
            ->children()
            ->scalarNode('traceMode')
                ->cannotBeEmpty()
                ->defaultValue(self::TRACEMODE_TRACECONTEXT)
                ->validate()
                    ->ifNotInArray([self::TRACEMODE_TRACECONTEXT, self::TRACEMODE_TRACEID])
                    ->thenInvalid('Invalid trace mode %s, must be either `' . self::TRACEMODE_TRACECONTEXT . '` or `' . self::TRACEMODE_TRACEID . '`')
                    ->end()
                ->info('The trace mode to use. Either `' . self::TRACEMODE_TRACECONTEXT . '` or `' . self::TRACEMODE_TRACEID . '`')
            ->end()
            ->arrayNode('traceid')
                ->children()
                    ->scalarNode('request_header')
                        ->cannotBeEmpty()
                        ->defaultValue('X-Trace-Id')
                        ->info('The header in which the bundle will look for and set trace IDs')
                    ->end()
                    ->scalarNode('response_header')
                        ->cannotBeEmpty()
                        ->defaultValue('X-Trace-Id')
                        ->info('The header the bundle will set the trace ID at in the response')
                    ->end()
                    ->scalarNode('generator_service')
                        ->info('The service name for the trace ID generator. Defaults to `symfony/uid` or `ramsey/uuid`')
                    ->end()
                ->end()
            ->end()
            ->booleanNode('trust_request_header')
                ->defaultValue(true)
                ->info("Whether or not to trust the incoming request's `Trace-Id` header as a real ID")
            ->end()
            ->scalarNode('storage_service')
                ->info('The service name for trace ID storage. Defaults to `TraceStorage`')
            ->end()
            ->booleanNode('enable_monolog')
                ->info('Whether or not to turn on the trace ID processor for monolog')
                ->defaultTrue()
            ->end()
            ->booleanNode('enable_console')
                ->info('Whether to add the trace id to console commands, defaults to true')
                ->defaultTrue()
            ->end()
            ->booleanNode('enable_messenger')
                ->info('Whether to add the trace id to message bus events, defaults to false')
                ->defaultFalse()
            ->end()
            ->booleanNode('enable_twig')
                ->info('Whether or not to enable the twig `trace_id()` and `transaction_id()` functions. Only works if TwigBundle is present.')
                ->defaultTrue()
            ->end()
            ->arrayNode('http_client')
                ->children()
                    ->booleanNode('enabled')
                        ->info('Whether or not to enable the trace id aware http client')
                        ->defaultTrue()
                    ->end()
                    ->booleanNode('tag_default_client')
                        ->info('Whether or not to tag the default http client')
                        ->defaultFalse()
                    ->end()
                    ->scalarNode('header')
                        ->info('The header the bundle set in the request in the http client')
                        ->defaultValue('X-Trace-Id')
                    ->end()
                ->end()
            ->end();

        return $tree;
    }
}
