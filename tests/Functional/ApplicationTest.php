<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Tests\Functional;

use DR\SymfonyTraceBundle\TraceContext;
use DR\SymfonyTraceBundle\TraceId;
use DR\SymfonyTraceBundle\TraceStorageInterface;
use Exception;
use PHPUnit\Framework\Attributes\CoversNothing;
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
    public function testCommandShouldSetTraceId(): void
    {
        $application = new Application(static::bootKernel(['environment' => 'test', 'debug' => false, 'tracemode' => TraceId::TRACEMODE]));

        $storage = self::getContainer()->get('request.id.storage');
        static::assertInstanceOf(TraceStorageInterface::class, $storage);

        $input  = new ArrayInput(['help']);
        $output = new NullOutput();

        $exitCode = $application->doRun($input, $output);
        static::assertSame(Command::SUCCESS, $exitCode);
        static::assertInstanceOf(TraceId::class, $storage->getTrace());
        static::assertNotNull($storage->getTraceId());
        static::assertNotNull($storage->getTransactionId());
    }

    /**
     * @throws Exception
     */
    public function testCommandShouldSetTraceContext(): void
    {
        $application = new Application(static::bootKernel(['environment' => 'test', 'debug' => false, 'tracemode' => TraceContext::TRACEMODE]));

        $storage = self::getContainer()->get('request.id.storage');
        static::assertInstanceOf(TraceStorageInterface::class, $storage);

        $input  = new ArrayInput(['help']);
        $output = new NullOutput();

        $exitCode = $application->doRun($input, $output);
        static::assertSame(Command::SUCCESS, $exitCode);
        static::assertInstanceOf(TraceContext::class, $storage->getTrace());
        static::assertNotNull($storage->getTraceId());
        static::assertNotNull($storage->getTransactionId());
    }
}
