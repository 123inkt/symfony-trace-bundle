<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle;

use DR\SymfonyTraceBundle\DependencyInjection\Compiler\HttpClientTracePass;
use DR\SymfonyTraceBundle\DependencyInjection\SymfonyTraceExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @codeCoverageIgnore - This is a bundle class, tested by the functional test
 */
final class SymfonyTraceBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new HttpClientTracePass());
    }

    /**
     * @inheritdoc
     */
    public function getContainerExtension(): ExtensionInterface
    {
        return new SymfonyTraceExtension();
    }
}
