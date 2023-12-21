<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Tests\Functional;

use DR\SymfonyTraceBundle\DependencyInjection\Configuration;
use DR\SymfonyTraceBundle\Tests\Functional\App\TestKernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AbstractWebTestCase extends WebTestCase
{
    /**
     * @param array{environment?: string, debug?: bool, tracemode?: string} $options
     */
    protected static function createKernel(array $options = []): TestKernel
    {
        $env = $options['environment'] ?? $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'test';
        $debug = $options['debug'] ?? $_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? false;

        return new TestKernel($env, $debug, $options['tracemode'] ?? Configuration::TRACEMODE_TRACEID);
    }
}
