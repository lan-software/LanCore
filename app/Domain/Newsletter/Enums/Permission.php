<?php

namespace App\Domain\Newsletter\Enums;

use App\Contracts\PermissionEnum;

/**
 * @see docs/mil-std-498/SRS.md NLT-F-001
 */
enum Permission: string implements PermissionEnum
{
    case ManageNewsletterLists = 'manage_newsletter_lists';
}
