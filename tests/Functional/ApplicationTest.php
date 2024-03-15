<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Tests\Functional;

use DR\SymfonyTraceBundle\DependencyInjection\Configuration;
use DR\SymfonyTraceBundle\TraceStorage;
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
    protected function tearDown(): void
    {
        parent::tearDown();
        putenv('TRACE_ID=');
    }

    /**
     * @throws Exception
     */
    #[TestWith(['defaults', TraceStorage::class])]
    #[TestWith([Configuration::TRACEMODE_TRACEID, 'request.id.storage'])]
    #[TestWith([Configuration::TRACEMODE_TRACECONTEXT, 'request.id.storage'])]
    public function testCommandShouldSetTrace(string $traceMode, string $storageServiceId): void
    {
        $application = new Application(static::bootKernel(['environment' => 'test', 'debug' => false, 'tracemode' => $traceMode]));

        $storage = self::getContainer()->get($storageServiceId);
        static::assertInstanceOf(TraceStorageInterface::class, $storage);

        $input  = new ArrayInput(['help']);
        $output = new NullOutput();

        $exitCode = $application->doRun($input, $output);
        static::assertSame(Command::SUCCESS, $exitCode);
        static::assertNotNull($storage->getTraceId());
        static::assertNotNull($storage->getTransactionId());
    }

    public function testCommandShouldTakeTraceIdFromEnv(): void
    {
        putenv('TRACE_ID=test-trace-id');

        $kernel = static::bootKernel(['environment' => 'test', 'debug' => false, 'tracemode' => Configuration::TRACEMODE_TRACEID]);

        $storage = self::getContainer()->get('request.id.storage');
        static::assertInstanceOf(TraceStorageInterface::class, $storage);

        static::assertSame(Command::SUCCESS, (new Application($kernel))->doRun(new ArrayInput(['help']), new NullOutput()));
        static::assertSame('test-trace-id', $storage->getTraceId());
    }
}
