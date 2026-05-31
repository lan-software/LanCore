<?php

namespace App\Domain\Event\Enums;

use App\Domain\Event\Enums\Concerns\InteractsWithBitset;

/**
 * Food policy at an event, stored as a summed bitset per the LAN Party
 * Publishing Standard v2 `foodPolicy` field.
 *
 * @see docs/mil-std-498/SSS.md CAP-PUB-001
 * @see docs/mil-std-498/SRS.md PUB-F-003
 */
enum FoodPolicy: int
{
    use InteractsWithBitset;

    case NoOutsideFood = 1;
    case ByobPermitted = 2;
    case SoldOnSite = 4;
    case FreeProvided = 8;

    public function label(): string
    {
        return match ($this) {
            self::NoOutsideFood => 'No outside food',
            self::ByobPermitted => 'Bring your own permitted',
            self::SoldOnSite => 'Food sold on site',
            self::FreeProvided => 'Free food provided',
        };
    }
}
