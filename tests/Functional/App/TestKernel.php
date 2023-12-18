<?php
declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Tests\Functional\App;

use DR\SymfonyTraceBundle\DependencyInjection\Configuration;
use DR\SymfonyTraceBundle\SymfonyTraceBundle;
use Exception;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel;

final class TestKernel extends Kernel
{
    public function __construct(string $environment, bool $debug, private string $traceMode = Configuration::TRACEMODE_TRACEID)
    {
        parent::__construct($environment, $debug);
    }

    /**
     * @return iterable<int|string, BundleInterface>
     */
    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new TwigBundle(),
            new MonologBundle(),
            new SymfonyTraceBundle(),
        ];
    }

    /**
     * @throws Exception
     */
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load($this->getProjectDir() . "/config/config.yml");
        if ($this->traceMode === Configuration::TRACEMODE_TRACEID) {
            $loader->load($this->getProjectDir() . "/config/traceid.yml");
        } elseif ($this->traceMode === Configuration::TRACEMODE_TRACECONTEXT) {
            $loader->load($this->getProjectDir() . "/config/tracecontext.yml");
        }
    }

    public function getLogDir(): string
    {
        return dirname(__DIR__, 3) . '/tmp/' . $this->traceMode;
    }

    public function getCacheDir(): string
    {
        return dirname(__DIR__, 3) . '/tmp/' . $this->traceMode;
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }
}
