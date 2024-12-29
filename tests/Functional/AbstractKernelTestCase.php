<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Tests\Functional;

use DR\SymfonyTraceBundle\DependencyInjection\Configuration;
use DR\SymfonyTraceBundle\Tests\Functional\App\TestKernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AbstractKernelTestCase extends KernelTestCase
{
    /**
     * @param array{environment?: string, debug?: bool, tracemode?: string} $options
     */
    protected static function createKernel(array $options = []): TestKernel
    {
        $env = $options['environment'] ?? $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'test';
        $debug = $options['debug'] ?? $_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? true;

        return new TestKernel((string)$env, (bool)$debug, $options['tracemode'] ?? Configuration::TRACEMODE_TRACEID);
    }
}
