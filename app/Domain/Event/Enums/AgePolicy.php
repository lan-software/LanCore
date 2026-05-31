<?php

namespace App\Domain\Event\Enums;

use App\Domain\Event\Enums\Concerns\InteractsWithBitset;

/**
 * Age policy at an event, stored as a summed bitset per the LAN Party
 * Publishing Standard v2 `agePolicy` field.
 *
 * @see docs/mil-std-498/SSS.md CAP-PUB-001
 * @see docs/mil-std-498/SRS.md PUB-F-003
 */
enum AgePolicy: int
{
    use InteractsWithBitset;

    case GuardianRequiredForMinors = 1;
    case MinimumAge12 = 2;
    case MinimumAge16 = 4;
    case MinimumAge18 = 8;

    public function label(): string
    {
        return match ($this) {
            self::GuardianRequiredForMinors => 'Guardian required for minors',
            self::MinimumAge12 => 'Minimum age 12',
            self::MinimumAge16 => 'Minimum age 16',
            self::MinimumAge18 => 'Minimum age 18',
        };
    }
}
