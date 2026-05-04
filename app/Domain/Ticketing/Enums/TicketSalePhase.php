<?php

namespace App\Domain\Ticketing\Enums;

/**
 * @see docs/mil-std-498/SDD.md §5.13
 * @see docs/mil-std-498/SRS.md NTF-F-008, NTF-F-010
 */
enum TicketSalePhase: string
{
    case Release = 'release';
    case End = 'end';
}
