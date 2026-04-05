<?php

namespace App\Domain\Ticketing\Enums;

/**
 * @see docs/mil-std-498/SSS.md CAP-TKT-005
 * @see docs/mil-std-498/SRS.md TKT-F-004
 */
enum TicketStatus: string
{
    case Active = 'active';
    case CheckedIn = 'checked_in';
    case Cancelled = 'cancelled';
}
