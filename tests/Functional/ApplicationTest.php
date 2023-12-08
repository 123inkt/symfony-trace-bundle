<?php

declare(strict_types=1);

namespace DR\SymfonyRequestId\Tests\Functional;

use DR\SymfonyRequestId\IdStorageInterface;
use Exception;
use PHPUnit\Framework\Attributes\CoversNothing;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

#[CoversNothing]
class ApplicationTest extends KernelTestCase
{
    /**
     * @throws Exception
     */
    public function testCommandShouldSetTraceId(): void
    {
        $application = new Application(static::bootKernel(['environment' => 'test', 'debug' => false]));

        $storage = self::getContainer()->get('request.id.storage');
        static::assertInstanceOf(IdStorageInterface::class, $storage);

        $input  = new ArrayInput(['help']);
        $output = new NullOutput();

        $exitCode = $application->doRun($input, $output);
        static::assertSame(Command::SUCCESS, $exitCode);
        static::assertNotNull($storage->getTraceId());
    }
}
