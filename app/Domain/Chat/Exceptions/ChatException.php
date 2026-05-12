<?php

namespace App\Domain\Chat\Exceptions;

use RuntimeException;

abstract class ChatException extends RuntimeException
{
    /**
     * Translation key for the user-facing error message.
     */
    abstract public function translationKey(): string;

    public function httpStatus(): int
    {
        return 422;
    }
}
