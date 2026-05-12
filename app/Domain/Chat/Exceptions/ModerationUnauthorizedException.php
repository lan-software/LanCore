<?php

namespace App\Domain\Chat\Exceptions;

class ModerationUnauthorizedException extends ChatException
{
    public function translationKey(): string
    {
        return 'chat.errors.moderation_unauthorized';
    }

    public function httpStatus(): int
    {
        return 403;
    }
}
