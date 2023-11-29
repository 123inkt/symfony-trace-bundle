<?php
declare(strict_types=1);

namespace DR\SymfonyRequestId\Tests\Acceptance\App\Messenger;

class TestMessageHandler
{
    public function __invoke(TestMessage $message): void
    {
    }
}
