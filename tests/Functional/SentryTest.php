<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Tests\Functional;

use DR\SymfonyTraceBundle\Tests\Functional\App\Service\MockSentryHub;
use Exception;
use PHPUnit\Framework\Attributes\CoversNothing;
use ReflectionClass;
use Sentry\State\HubInterface;
use Sentry\State\Scope;

#[CoversNothing]
class SentryTest extends AbstractWebTestCase
{
    /**
     * @throws Exception
     */
    public function testTraceIdIsAddedToSentryHub(): void
    {
        $client = self::createClient();
        $hub    = self::getContainer()->get('test.sentry.hub');
        static::assertInstanceOf(MockSentryHub::class, $hub);

        $client->request('GET', '/', [], [], ['HTTP_TRACE_ID' => 'testId']);
        static::assertResponseIsSuccessful();

        $scope = $hub->getScope();
        self::assertScopeHasTag($scope, 'trace_id');
        self::assertScopeHasTag($scope, 'transaction_id');
    }

    private static function assertScopeHasTag(Scope $scope, string $key): void
    {
        $class    = new ReflectionClass($scope);
        $property = $class->getProperty('tags');

        $tags = $property->getValue($scope);
        static::assertIsArray($tags);
        static::assertArrayHasKey($key, $tags);
    }
}
