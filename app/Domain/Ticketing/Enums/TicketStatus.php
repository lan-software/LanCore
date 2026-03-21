<?php

namespace App\Domain\Ticketing\Enums;

enum TicketStatus: string
{
    case Active = 'active';
    case CheckedIn = 'checked_in';
    case Cancelled = 'cancelled';
}
