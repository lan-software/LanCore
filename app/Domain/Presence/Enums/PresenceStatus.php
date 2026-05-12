<?php

namespace App\Domain\Presence\Enums;

/**
 * @see docs/mil-std-498/SRS.md PRS-F-001
 */
enum PresenceStatus: string
{
    case Active = 'active';
    case Idle = 'idle';
    case Offline = 'offline';

    public function label(): string
    {
        return __('presence.status.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'green',
            self::Idle => 'yellow',
            self::Offline => 'grey',
        };
    }

    public function priority(): int
    {
        return match ($this) {
            self::Active => 0,
            self::Idle => 1,
            self::Offline => 2,
        };
    }
}
