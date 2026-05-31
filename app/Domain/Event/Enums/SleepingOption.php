<?php

namespace App\Domain\Event\Enums;

use App\Domain\Event\Enums\Concerns\InteractsWithBitset;

/**
 * Sleeping arrangements at an event, stored as a summed bitset per the
 * LAN Party Publishing Standard v2 `sleeping` field.
 *
 * @see docs/mil-std-498/SSS.md CAP-PUB-001
 * @see docs/mil-std-498/SRS.md PUB-F-003
 */
enum SleepingOption: int
{
    use InteractsWithBitset;

    case NotOvernight = 1;
    case PrivateRooms = 2;
    case SharedRooms = 4;
    case Camping = 8;

    public function label(): string
    {
        return match ($this) {
            self::NotOvernight => 'Not overnight',
            self::PrivateRooms => 'Private rooms',
            self::SharedRooms => 'Shared rooms',
            self::Camping => 'Camping',
        };
    }
}
