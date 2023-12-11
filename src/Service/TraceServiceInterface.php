<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Service;

use DR\SymfonyTraceBundle\TraceContext;
use DR\SymfonyTraceBundle\TraceId;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface TraceServiceInterface
{
    public function supports(Request $request): bool;
    public function createNewTrace(): TraceId|TraceContext;

    public function getRequestTrace(Request $request): TraceId|TraceContext;
    public function handleResponse(Response $response, TraceId|TraceContext $context): void;

    public function handleClientRequest(TraceId|TraceContext $trace, string $method, string $url, array $options = []): array;
}
