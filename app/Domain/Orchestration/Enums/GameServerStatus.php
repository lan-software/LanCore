<?php

namespace App\Domain\Orchestration\Enums;

/**
 * @see docs/mil-std-498/SRS.md ORC-F-002
 */
enum GameServerStatus: string
{
    case Available = 'available';
    case InUse = 'in_use';
    case Offline = 'offline';
    case Maintenance = 'maintenance';

    public function isUsable(): bool
    {
        return $this === self::Available;
    }
}
