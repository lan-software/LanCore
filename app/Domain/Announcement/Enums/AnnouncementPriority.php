<?php

namespace App\Domain\Announcement\Enums;

enum AnnouncementPriority: string
{
    case Silent = 'silent';
    case Normal = 'normal';
    case Emergency = 'emergency';
}
