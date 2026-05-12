<?php

namespace App\Domain\Chat\Exceptions;

class PostUnauthorizedException extends ChatException
{
    public function translationKey(): string
    {
        return 'chat.errors.post_unauthorized';
    }

    public function httpStatus(): int
    {
        return 403;
    }
}
