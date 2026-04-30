<?php

namespace App\Domain\DataLifecycle\Enums;

enum DeletionInitiator: string
{
    case User = 'user';
    case Admin = 'admin';
}
