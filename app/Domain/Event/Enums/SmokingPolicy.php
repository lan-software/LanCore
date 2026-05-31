<?php

namespace App\Domain\Event\Enums;

use App\Domain\Event\Enums\Concerns\InteractsWithBitset;

/**
 * Smoking policy at an event, stored as a summed bitset per the LAN Party
 * Publishing Standard v2 `smokingPolicy` field.
 *
 * @see docs/mil-std-498/SSS.md CAP-PUB-001
 * @see docs/mil-std-498/SRS.md PUB-F-003
 */
enum SmokingPolicy: int
{
    use InteractsWithBitset;

    case Prohibited = 1;
    case OutdoorArea = 2;
    case IndoorArea = 4;
    case VapingAllowed = 8;

    public function label(): string
    {
        return match ($this) {
            self::Prohibited => 'Prohibited',
            self::OutdoorArea => 'Designated outdoor area',
            self::IndoorArea => 'Designated indoor area',
            self::VapingAllowed => 'Vaping allowed',
        };
    }
}
