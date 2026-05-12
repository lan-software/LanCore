<?php

namespace App\Domain\Chat\Exceptions;

class RateLimitExceededException extends ChatException
{
    public function __construct(public readonly string $window)
    {
        parent::__construct("Chat rate limit exceeded ({$window} window).");
    }

    public function translationKey(): string
    {
        return 'chat.errors.rate_limit_'.$this->window;
    }

    public function httpStatus(): int
    {
        return 429;
    }
}
