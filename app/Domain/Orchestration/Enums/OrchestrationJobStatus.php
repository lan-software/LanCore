<?php

namespace App\Domain\Orchestration\Enums;

/**
 * @see docs/mil-std-498/SRS.md ORC-F-009
 */
enum OrchestrationJobStatus: string
{
    case Pending = 'pending';
    case SelectingServer = 'selecting_server';
    case Deploying = 'deploying';
    case Active = 'active';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    /**
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::SelectingServer, self::Failed, self::Cancelled],
            self::SelectingServer => [self::Deploying, self::Failed],
            self::Deploying => [self::Active, self::Failed],
            self::Active => [self::Completed, self::Failed],
            self::Completed => [],
            self::Failed => [self::Pending, self::Cancelled],
            self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions());
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Failed, self::Cancelled]);
    }
}
