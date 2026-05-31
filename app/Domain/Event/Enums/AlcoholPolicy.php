<?php

namespace App\Domain\Event\Enums;

use App\Domain\Event\Enums\Concerns\InteractsWithBitset;

/**
 * Alcohol policy at an event, stored as a summed bitset per the LAN Party
 * Publishing Standard v2 `alcoholPolicy` field.
 *
 * @see docs/mil-std-498/SSS.md CAP-PUB-001
 * @see docs/mil-std-498/SRS.md PUB-F-003
 */
enum AlcoholPolicy: int
{
    use InteractsWithBitset;

    case Prohibited = 1;
    case ByobPermitted = 2;
    case SoldOnSite = 4;
    case DesignatedAreaOnly = 8;

    public function label(): string
    {
        return match ($this) {
            self::Prohibited => 'Prohibited',
            self::ByobPermitted => 'BYOB permitted',
            self::SoldOnSite => 'Sold on site',
            self::DesignatedAreaOnly => 'Designated area only',
        };
    }
}
