<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Tests\Functional;

use DR\SymfonyTraceBundle\DependencyInjection\Configuration;
use DR\SymfonyTraceBundle\Generator\TraceIdGeneratorInterface;
use DR\SymfonyTraceBundle\Tests\Functional\App\Monolog\MemoryHandler;
use DR\SymfonyTraceBundle\TraceContext;
use DR\SymfonyTraceBundle\TraceStorageInterface;
use Exception;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\TestWith;

#[CoversNothing]
class RequestHandleTest extends AbstractWebTestCase
{
    /**
     * @throws Exception
     */
    public function testRequestThatAlreadyHasATraceIdDoesNotReplaceIt(): void
    {
        $client = self::createClient();

        $crawler = $client->request('GET', '/', [], [], ['HTTP_TRACE_ID' => 'testId']);
        static::assertResponseIsSuccessful();

        $response = $client->getResponse();
        static::assertSame('testId', $response->headers->get('Trace-Id'));
        static::assertSame('testId', self::getService(TraceStorageInterface::class)->getTraceId());
        self::assertLogsHaveTraceId('testId');
        static::assertGreaterThan(
            0,
            $crawler->filter('h1:contains("testId")')->count(),
            'should have the request ID in the response HTML'
        );
    }

    /**
     * @throws Exception
     */
    public function testRequestWithOutTraceIdCreatesOneAndPassesThroughTheResponse(): void
    {
        $client = self::createClient();

        $crawler = $client->request('GET', '/');
        static::assertResponseIsSuccessful();

        $id = self::getService(TraceStorageInterface::class)->getTraceId();
        static::assertNotEmpty($id);
        static::assertSame($id, $client->getResponse()->headers->get('Trace-Id'));
        self::assertLogsHaveTraceId($id);
        static::assertGreaterThan(
            0,
            $crawler->filter(sprintf('h1:contains("%s")', $id))->count(),
            'should have the request ID in the response HTML'
        );
    }

    /**
     * @throws Exception
     */
    public function testRequestThatAlreadyHasATraceContextDoesNotReplaceIt(): void
    {
        $client = self::createClient(['tracemode' => Configuration::TRACEMODE_TRACECONTEXT]);

        $crawler = $client->request('GET', '/', [], [], ['HTTP_TRACEPARENT' => '00-0af7651916cd43dd8448eb211c80319c-b7ad6b7169203331-00']);
        static::assertResponseIsSuccessful();

        static::assertSame('0af7651916cd43dd8448eb211c80319c', self::getService(TraceStorageInterface::class)->getTraceId());
        self::assertLogsHaveTraceId('0af7651916cd43dd8448eb211c80319c');
        static::assertGreaterThan(
            0,
            $crawler->filter('h1:contains("0af7651916cd43dd8448eb211c80319c")')->count(),
            'should have the request ID in the response HTML'
        );
    }

    /**
     * @throws Exception
     */
    public function testRequestWithOutTraceContextCreatesOneAndPassesThroughTheResponse(): void
    {
        $client = self::createClient(['tracemode' => Configuration::TRACEMODE_TRACECONTEXT]);

        $crawler = $client->request('GET', '/');
        static::assertResponseIsSuccessful();

        $id = self::getService(TraceStorageInterface::class)->getTraceId();
        static::assertNotEmpty($id);
        self::assertLogsHaveTraceId($id);
        static::assertGreaterThan(
            0,
            $crawler->filter(sprintf('h1:contains("%s")', $id))->count(),
            'should have the request ID in the response HTML'
        );
    }

    /**
     * @param class-string $class
     *
     * @throws Exception
     */
    #[TestWith([TraceStorageInterface::class])]
    #[TestWith([TraceIdGeneratorInterface::class])]
    public function testExpectedServicesArePubliclyAvailableFromTheContainer(string $class): void
    {
        /** @var object $service */
        $service = self::getContainer()->get($class);

        static::assertInstanceOf($class, $service);
    }

    /**
     * @throws Exception
     */
    private static function assertLogsHaveTraceId(string $id): void
    {
        /** @var string[] $logs */
        $logs = self::getService(MemoryHandler::class, 'log.memory_handler')->getLogs();
        foreach ($logs as $message) {
            static::assertStringContainsString($id, $message);
        }
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     *
     * @return T
     * @throws Exception
     */
    private static function getService(string $class, string $id = null): object
    {
        $service = self::getContainer()->get($id ?? $class);
        static::assertInstanceOf($class, $service);

        return $service;
    }
}
