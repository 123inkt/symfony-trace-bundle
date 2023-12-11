<?php

declare(strict_types=1);

namespace DR\SymfonyRequestId\Tests\Functional;

use DR\SymfonyRequestId\Tests\Functional\App\TestKernel;
use DR\SymfonyRequestId\TraceId;
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

        return new TestKernel($env, $debug, $options['tracemode'] ?? TraceId::TRACEMODE);
    }
}
