<?php
declare(strict_types=1);

namespace DR\SymfonyTraceBundle;

final class TraceStorage implements TraceStorageInterface
{
    private TraceContext $trace;

    public function __construct()
    {
        $this->trace = new TraceContext();
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

    public function getTrace(): TraceContext
    {
        return $this->trace;
    }

    public function setTrace(TraceContext $trace): void
    {
        $this->trace = $trace;
    }
}
