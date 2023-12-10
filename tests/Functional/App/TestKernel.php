<?php
declare(strict_types=1);

namespace DR\SymfonyRequestId\Tests\Functional\App;

use DR\SymfonyRequestId\RequestIdBundle;
use DR\SymfonyRequestId\TraceId;
use Exception;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel;

final class TestKernel extends Kernel
{
    public function __construct(string $environment, bool $debug, private string $traceMode = TraceId::TRACEMODE)
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
            new RequestIdBundle(),
        ];
    }

    /**
     * @throws Exception
     */
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load($this->getProjectDir() . "/config/config.yml");
        if ($this->traceMode === TraceId::TRACEMODE) {
            $loader->load($this->getProjectDir() . "/config/traceid.yml");
        } else {
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
