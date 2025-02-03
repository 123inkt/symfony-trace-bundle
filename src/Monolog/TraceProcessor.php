<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Monolog;

use DR\SymfonyTraceBundle\TraceStorageInterface;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Adds the trace + transaction IDs to the Monolog record's `extra` key, so it can be used in formatters, etc.
 * @internal
 */
final class TraceProcessor implements ProcessorInterface
{
    public function __construct(private readonly TraceStorageInterface $storage)
    {
    }

    /**
     * @param array<string, mixed[]>|LogRecord $record
     * @inheritDoc
     */
    public function __invoke(array|LogRecord $record): array|LogRecord
    {
        $traceId       = $this->storage->getTraceId();
        $transactionId = $this->storage->getTransactionId();

        if ($traceId !== null) {
            $record = $this->setExtraValue($traceId, 'trace_id', $record);
        }
        if ($transactionId !== null) {
            $record = $this->setExtraValue($transactionId, 'transaction_id', $record);
        }

        return $record;
    }

    /**
     * @param array<string, mixed[]>|LogRecord $record
     * @return array<string, mixed[]>|LogRecord
     */
    private function setExtraValue(?string $id, string $key, array|LogRecord $record): array|LogRecord
    {
        if ($id !== null) {
            // @codeCoverageIgnoreStart
            if (is_array($record)) {
                $record['extra'][$key] = $id;
                // @codeCoverageIgnoreEnd
            } else {
                $record->extra[$key] = $id;
            }
        }

        return $record;
    }
}
