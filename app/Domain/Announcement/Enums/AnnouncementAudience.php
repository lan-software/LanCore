<?php

namespace App\Domain\Announcement\Enums;

enum AnnouncementAudience: string
{
    case Internal = 'internal';
    case LancoreOnly = 'lancore_only';
    case Satellites = 'satellites';
    case All = 'all';
}
