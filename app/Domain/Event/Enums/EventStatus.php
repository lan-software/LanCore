<?php

namespace App\Domain\Event\Enums;

/**
 * @see docs/mil-std-498/SSS.md CAP-EVT-002
 * @see docs/mil-std-498/SRS.md EVT-F-004
 */
enum EventStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}
