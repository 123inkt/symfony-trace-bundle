<?php

declare(strict_types=1);

namespace DR\SymfonyRequestId\Tests\Functional\App\Service;

use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

class MockClientCallbackHelper
{
    /**
     * @param array{
     *     headers: string[]
     * } $options
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __invoke(string $method, string $url, array $options): ResponseInterface
    {
        $headers = [];

        foreach ($options['headers'] as $header) {
            [$key, $value] = explode(': ', $header);
            $headers[$key] = $value;
        }

        return new MockResponse('success', [
            'response_headers' => $headers
        ]);
    }
}
