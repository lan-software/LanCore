<?php

namespace App\Domain\Chat\Enums;

/**
 * @see docs/mil-std-498/SRS.md CHT-F-001
 */
enum RoomStatus: string
{
    case Open = 'open';
    case WriteLocked = 'write_locked';
    case Archived = 'archived';

    public function allowsSubscribe(): bool
    {
        return $this !== self::Archived;
    }

    public function allowsPost(): bool
    {
        return $this === self::Open;
    }
}
