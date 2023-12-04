<?php

declare(strict_types=1);

namespace DR\SymfonyRequestId\Monolog;

use DR\SymfonyRequestId\RequestIdStorageInterface;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Adds the request ID to the Monolog record's `extra` key, so it can be used in formatters, etc.
 * @internal
 */
final class RequestIdProcessor implements ProcessorInterface
{
    public function __construct(private readonly RequestIdStorageInterface $storage)
    {
    }

    /**
     * @inheritDoc
     */
    public function __invoke(array|LogRecord $record): array|LogRecord
    {
        $id = $this->storage->getRequestId();
        if ($id !== null) {
            // @codeCoverageIgnoreStart
            if (is_array($record)) {
                $record['extra']['request_id'] = $id;
                // @codeCoverageIgnoreEnd
            } else {
                $record->extra['request_id'] = $id;
            }
        }

        return $record;
    }
}
