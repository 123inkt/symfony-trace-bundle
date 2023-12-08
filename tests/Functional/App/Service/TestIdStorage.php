<?php
declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Tests\Functional\App\Service;

use DR\SymfonyTraceBundle\IdStorageInterface;

class TestIdStorage implements IdStorageInterface
{
    public int $getTransactionIdCount = 0;
    public int $setTransactionIdCount = 0;
    public int $getTraceIdCount = 0;
    public int $setTraceIdCount = 0;
    private ?string $transactionId = null;
    private ?string $traceId = null;

    public function getTransactionId(): ?string
    {
        ++$this->getTransactionIdCount;

        return $this->transactionId;
    }

    public function setTransactionId(?string $id): void
    {
        $this->transactionId = $id;
        ++$this->setTransactionIdCount;
    }


    public function getTraceId(): ?string
    {
        ++$this->getTraceIdCount;

        return $this->traceId;
    }

    public function setTraceId(?string $id): void
    {
        $this->traceId = $id;
        ++$this->setTraceIdCount;
    }
}
