<?php

namespace App\Domain\Chat\Exceptions;

class DuplicateMessageException extends ChatException
{
    public function translationKey(): string
    {
        return 'chat.errors.duplicate_message';
    }
}
