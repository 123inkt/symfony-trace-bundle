<?php

declare(strict_types=1);

namespace Functional;

use DR\SymfonyRequestId\Tests\Functional\App\Service\TestIdStorage;
use PHPUnit\Framework\Attributes\CoversNothing;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[CoversNothing]
class HttpClientTest extends KernelTestCase
{
    public function testHttpClientIsDecorated(): void
    {
        /** @var TestIdStorage $storage */
        $storage = static::getContainer()->get('request.id.storage');
        /** @var HttpClientInterface $client */
        $client  = static::getContainer()->get('test.http_client');

        $storage->setTraceId('123');

        $response = $client->request('GET', 'https://example.com');

        self::assertArrayHasKey('trace-id', $response->getHeaders());
        self::assertSame('123', $response->getHeaders()['trace-id'][0]);
    }
}
