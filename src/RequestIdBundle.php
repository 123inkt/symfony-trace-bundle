<?php

declare(strict_types=1);

namespace DR\SymfonyRequestId;

use DR\SymfonyRequestId\DependencyInjection\Compiler\HttpClientRequestIdPass;
use DR\SymfonyRequestId\DependencyInjection\SymfonyRequestIdExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @codeCoverageIgnore - This is a bundle class, tested by the acceptance test
 * @internal
 */
final class RequestIdBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new HttpClientRequestIdPass());
    }

    /**
     * @inheritdoc
     */
    public function getContainerExtension(): ExtensionInterface
    {
        return new SymfonyRequestIdExtension();
    }
}
