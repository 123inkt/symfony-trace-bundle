<?php
declare(strict_types=1);

namespace DR\SymfonyRequestId;

final class TraceStorage implements TraceStorageInterface
{
    private TraceContext|TraceId $trace;

    public function __construct()
    {
        $this->trace = new TraceId();
    }

    public function getTransactionId(): ?string
    {
        return $this->trace->getTransactionId();
    }

    public function setTransactionId(?string $id): void
    {
        $this->trace->setTransactionId($id);
    }

    public function getTraceId(): ?string
    {
        return $this->trace->getTraceId();
    }

    public function setTraceId(?string $id): void
    {
        $this->trace->setTraceId($id);
    }

    public function getTrace(): TraceId|TraceContext
    {
        return $this->trace;
    }

    public function setTrace(TraceId|TraceContext $trace): void
    {
        $this->trace = $trace;
    }
}
