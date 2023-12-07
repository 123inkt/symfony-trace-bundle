<?php

declare(strict_types=1);

namespace DR\SymfonyRequestId\Monolog;

use DR\SymfonyRequestId\IdStorageInterface;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Adds the request ID to the Monolog record's `extra` key, so it can be used in formatters, etc.
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
        $id = $this->storage->getTraceId();
        if ($id !== null) {
            // @codeCoverageIgnoreStart
            if (is_array($record)) {
                $record['extra']['trace_id'] = $id;
                // @codeCoverageIgnoreEnd
            } else {
                $record->extra['trace_id'] = $id;
            }
        }

        return $record;
    }
}
