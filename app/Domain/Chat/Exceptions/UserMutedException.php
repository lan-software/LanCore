<?php

namespace App\Domain\Chat\Exceptions;

class UserMutedException extends ChatException
{
    public function translationKey(): string
    {
        return 'chat.errors.user_muted';
    }
}
