<?php

declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Monolog;

use DR\SymfonyTraceBundle\IdStorageInterface;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Adds the trace + transaction IDs to the Monolog record's `extra` key, so it can be used in formatters, etc.
 * @internal
 */
final class TraceIdProcessor implements ProcessorInterface
{
    public function __construct(private readonly IdStorageInterface $storage)
    {
    }

    /**
     * @inheritDoc
     */
    public function __invoke(array|LogRecord $record): array|LogRecord
    {
        $record = $this->setExtraValue($this->storage->getTraceId(), 'trace_id', $record);
        $record = $this->setExtraValue($this->storage->getTransactionId(), 'transaction_id', $record);

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
