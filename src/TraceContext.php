<?php

declare(strict_types=1);

namespace DR\SymfonyRequestId;

/**
 * The trace context is a container for the traceparent and tracestate header values
 */
class TraceContext
{
    public const TRACEMODE = 'tracecontext';

    private ?string $transactionId = null;

    public function __construct(
        private string $version = '00',
        private ?string $traceId = null,
        private ?string $parentTransactionId = null,
        private string $flags = '00',
        private array  $traceState = []
    ) {
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @internal
     */
    public function setVersion(string $version): void
    {
        $this->version = $version;
    }

    public function getTraceId(): string
    {
        return $this->traceId;
    }

    /**
     * @internal
     */
    public function setTraceId(string $traceId): void
    {
        $this->traceId = $traceId;
    }

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    public function setTransactionId(string $transactionId): void
    {
        $this->transactionId = $transactionId;
    }

    public function getParentTransactionId(): ?string
    {
        return $this->parentTransactionId;
    }

    /**
     * @internal
     */
    public function setParentTransactionId(string $parentId): void
    {
        $this->parentTransactionId = $parentId;
    }

    public function getFlags(): string
    {
        return $this->flags;
    }

    /**
     * @internal
     */
    public function setFlags(string $flags): void
    {
        $this->flags = $flags;
    }

    public function getTraceState(): array
    {
        return $this->traceState;
    }

    /**
     * @internal
     */
    public function setTraceState(array $traceState): void
    {
        $this->traceState = $traceState;
    }
}
