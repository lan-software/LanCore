<?php

namespace App\Domain\Ticketing\Enums;

/**
 * @see docs/mil-std-498/SSS.md CAP-TKT-012
 * @see docs/mil-std-498/SRS.md TKT-F-015
 */
enum CheckInMode: string
{
    case Individual = 'individual';
    case Group = 'group';
}
