<?php
declare(strict_types=1);

namespace DR\SymfonyTraceBundle\Tests\Functional\App\Service;

use RuntimeException;
use Sentry\Breadcrumb;
use Sentry\CheckInStatus;
use Sentry\ClientInterface;
use Sentry\Event;
use Sentry\EventHint;
use Sentry\EventId;
use Sentry\Integration\IntegrationInterface;
use Sentry\MonitorConfig;
use Sentry\Severity;
use Sentry\State\HubInterface;
use Sentry\State\Scope;
use Sentry\Tracing\Span;
use Sentry\Tracing\Transaction;
use Sentry\Tracing\TransactionContext;
use Throwable;

class MockSentryHub implements HubInterface
{
    private Scope $scope;

    public function __construct()
    {
        $this->scope = new Scope();
    }

    public function getClient(): ?ClientInterface
    {
        return null;
    }

    public function getLastEventId(): ?EventId
    {
        return null;
    }

    public function pushScope(): Scope
    {
        return $this->scope;
    }

    public function popScope(): bool
    {
        return true;
    }

    public function getScope(): Scope
    {
        return $this->scope;
    }

    public function withScope(callable $callback): mixed
    {
        return $callback($this->scope);
    }

    public function configureScope(callable $callback): void
    {
        $callback($this->scope);
    }

    /**
     * @inheritDoc
     */
    public function bindClient(ClientInterface $client): void
    {
        // nothing
    }

    /**
     * @inheritDoc
     */
    public function captureMessage(string $message, ?Severity $level = null, ?EventHint $hint = null): ?EventId
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function captureException(Throwable $exception, ?EventHint $hint = null): ?EventId
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function captureEvent(Event $event, ?EventHint $hint = null): ?EventId
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function captureLastError(?EventHint $hint = null): ?EventId
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function addBreadcrumb(Breadcrumb $breadcrumb): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function captureCheckIn(
        string $slug,
        CheckInStatus $status,
        $duration = null,
        ?MonitorConfig $monitorConfig = null,
        ?string $checkInId = null
    ): ?string {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getIntegration(string $className): ?IntegrationInterface
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function startTransaction(TransactionContext $context, array $customSamplingContext = []): Transaction
    {
        throw new RuntimeException('Not implemented');
    }

    public function getTransaction(): ?Transaction
    {
        return null;
    }

    public function getSpan(): ?Span
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function setSpan(?Span $span): HubInterface
    {
        return $this;
    }
}
