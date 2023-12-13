<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Tests\Functional;

use DR\SymfonyTraceBundle\DependencyInjection\Configuration;
use DR\SymfonyTraceBundle\TraceStorageInterface;
use Exception;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

#[CoversNothing]
class ApplicationTest extends AbstractKernelTestCase
{
    /**
     * @throws Exception
     */
    #[TestWith([Configuration::TRACEMODE_TRACEID])]
    #[TestWith([Configuration::TRACEMODE_TRACECONTEXT])]
    public function testCommandShouldSetTrace(string $traceMode): void
    {
        $application = new Application(
            static::bootKernel(['environment' => 'test', 'debug' => false, 'tracemode' => $traceMode])
        );

        $storage = self::getContainer()->get('request.id.storage');
        static::assertInstanceOf(TraceStorageInterface::class, $storage);

        $input  = new ArrayInput(['help']);
        $output = new NullOutput();

        $exitCode = $application->doRun($input, $output);
        static::assertSame(Command::SUCCESS, $exitCode);
        static::assertNotNull($storage->getTraceId());
        static::assertNotNull($storage->getTransactionId());
    }
}
