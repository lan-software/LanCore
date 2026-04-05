<?php

namespace App\Domain\Competition\Enums;

/**
 * @see docs/mil-std-498/SRS.md COMP-F-001
 */
enum CompetitionStatus: string
{
    case Draft = 'draft';
    case RegistrationOpen = 'registration_open';
    case RegistrationClosed = 'registration_closed';
    case Running = 'running';
    case Finished = 'finished';
    case Archived = 'archived';

    public function isRegistrationPhase(): bool
    {
        return $this === self::RegistrationOpen;
    }

    public function isActive(): bool
    {
        return $this === self::Running;
    }

    /**
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::RegistrationOpen],
            self::RegistrationOpen => [self::RegistrationClosed],
            self::RegistrationClosed => [self::Running],
            self::Running => [self::Finished],
            self::Finished => [self::Archived],
            self::Archived => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions());
    }
}
