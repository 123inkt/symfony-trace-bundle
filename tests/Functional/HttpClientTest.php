<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Tests\Functional;

use DR\SymfonyTraceBundle\DependencyInjection\Configuration;
use DR\SymfonyTraceBundle\TraceContext;
use DR\SymfonyTraceBundle\TraceStorageInterface;
use PHPUnit\Framework\Attributes\CoversNothing;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[CoversNothing]
class HttpClientTest extends AbstractKernelTestCase
{
    public function testHttpClientIsDecoratedTraceId(): void
    {
        static::bootKernel(['environment' => 'test', 'debug' => false, 'tracemode' => Configuration::TRACEMODE_TRACEID]);

        /** @var TraceStorageInterface $storage */
        $storage = static::getContainer()->get('request.id.storage');
        /** @var HttpClientInterface $client */
        $client = static::getContainer()->get('test.http_client');

        $storage->setTraceId('123');

        $response = $client->request('GET', 'https://example.com');
        self::assertArrayHasKey('trace-id', $response->getHeaders());
        self::assertSame('123', $response->getHeaders()['trace-id'][0]);
    }

    public function testHttpClientIsDecoratedTraceContext(): void
    {
        static::bootKernel(['tracemode' => Configuration::TRACEMODE_TRACECONTEXT]);

        /** @var TraceStorageInterface $storage */
        $storage = static::getContainer()->get('request.id.storage');
        /** @var HttpClientInterface $client */
        $client = static::getContainer()->get('test.http_client');

        $trace = new TraceContext();
        $trace->setTraceId('123');
        $trace->setTransactionId('ABC');
        $storage->setTrace($trace);

        $response = $client->request('GET', 'https://example.com');
        self::assertArrayHasKey('traceparent', $response->getHeaders());
        self::assertSame('00-123-ABC-00', $response->getHeaders()['traceparent'][0]);
        self::assertArrayNotHasKey('tracestate', $response->getHeaders());
    }

    public function testHttpClientIsDecoratedTraceContextTraceState(): void
    {
        static::bootKernel(['tracemode' => Configuration::TRACEMODE_TRACECONTEXT]);

        /** @var TraceStorageInterface $storage */
        $storage = static::getContainer()->get('request.id.storage');
        /** @var HttpClientInterface $client */
        $client = static::getContainer()->get('test.http_client');

        $trace = new TraceContext();
        $trace->setTraceId('123');
        $trace->setTransactionId('ABC');
        $trace->setTraceState(['dr' => 'unittest', 'foo' => 'bar']);
        $storage->setTrace($trace);

        $response = $client->request('GET', 'https://example.com');
        self::assertArrayHasKey('traceparent', $response->getHeaders());
        self::assertSame('00-123-ABC-00', $response->getHeaders()['traceparent'][0]);
        self::assertArrayHasKey('tracestate', $response->getHeaders());
        self::assertSame('dr=unittest,foo=bar', $response->getHeaders()['tracestate'][0]);
    }
}
