<?php

namespace App\Domain\Event\Enums;

enum EventStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}
