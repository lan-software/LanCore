<?php

namespace App\Domain\Webhook\Enums;

enum WebhookEvent: string
{
    case UserRegistered = 'user.registered';

    public function label(): string
    {
        return match ($this) {
            self::UserRegistered => 'User Registered',
        };
    }
}
