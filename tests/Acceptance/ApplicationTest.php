<?php

declare(strict_types=1);

namespace DR\SymfonyRequestId\Tests\Acceptance;

use DR\SymfonyRequestId\RequestIdStorageInterface;
use DR\SymfonyRequestId\SimpleIdStorage;
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
    public function testCommandShouldSetRequestId(): void
    {
        $application = new Application(static::bootKernel(['environment' => 'test', 'debug' => false]));
        /** @var RequestIdStorageInterface $storage */
        $storage = self::getContainer()->get(SimpleIdStorage::class);

        $input  = new ArrayInput(['help']);
        $output = new NullOutput();

        $exitCode = $application->doRun($input, $output);
        static::assertSame(Command::SUCCESS, $exitCode);
        static::assertNotNull($storage->getRequestId());
    }
}
