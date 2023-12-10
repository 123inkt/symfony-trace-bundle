<?php
declare(strict_types=1);

namespace DR\SymfonyRequestId\Tests\Functional\App\Service;

use DR\SymfonyRequestId\TraceId;
use DR\SymfonyRequestId\TraceContext;
use DR\SymfonyRequestId\TraceStorageInterface;

class TestTraceStorage implements TraceStorageInterface
{
    public int $getTransactionIdCount = 0;
    public int $setTransactionIdCount = 0;
    public int $getTraceIdCount = 0;
    public int $setTraceIdCount = 0;

    private TraceId|TraceContext $trace;

    public function __construct()
    {
        $this->trace = new TraceId();
    }

    public function getTransactionId(): ?string
    {
        ++$this->getTransactionIdCount;

        return $this->trace->getTransactionId();
    }

    public function setTransactionId(?string $id): void
    {
        $this->trace->setTransactionId($id);
        ++$this->setTransactionIdCount;
    }


    public function getTraceId(): ?string
    {
        ++$this->getTraceIdCount;

        return $this->trace->getTraceId();
    }

    public function setTraceId(?string $id): void
    {
        $this->trace->setTraceId($id);
        ++$this->setTraceIdCount;
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
