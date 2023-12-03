<?php
declare(strict_types=1);

namespace DR\SymfonyRequestId\Tests\Acceptance\App\Monolog;

use Countable;
use DR\Utils\Assert;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;

use function count;
use function is_array;

final class MemoryHandler extends AbstractProcessingHandler implements Countable
{
    /** @var string[] */
    private array $logs = [];

    protected function write(array|LogRecord $record): void
    {
        if (is_array($record)) {
            $this->logs[] = Assert::string($record['formatted']);
        } else {
            $this->logs[] = Assert::string($record->formatted);
        }
    }

    public function count(): int
    {
        return count($this->logs);
    }

    /**
     * @return string[]
     */
    public function getLogs(): array
    {
        return $this->logs;
    }
}
