<?php

declare(strict_types=1);

namespace DR\SymfonyRequestId\Tests\Functional;

use DR\SymfonyRequestId\Tests\Functional\App\TestKernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AbstractKernelTestCase extends KernelTestCase
{
    protected static function createKernel(array $options = []): TestKernel
    {
        return new TestKernel($options['environment'], $options['debug'], $options['tracemode']);
    }
}
