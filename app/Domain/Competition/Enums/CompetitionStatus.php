<?php

namespace App\Domain\Competition\Enums;

/**
 * @see docs/mil-std-498/SRS.md COMP-F-001
 */
enum CompetitionStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
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
     * True for any status where the competition is visible on the public event
     * page (Published preheats display before registration actually opens).
     */
    public function isPubliclyVisible(): bool
    {
        return $this === self::Published || $this === self::RegistrationOpen;
    }

    /**
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Published],
            self::Published => [self::Draft, self::RegistrationOpen],
            self::RegistrationOpen => [self::RegistrationClosed],
            self::RegistrationClosed => [self::RegistrationOpen, self::Running],
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
