<?php

namespace App\Domain\Chat\Exceptions;

class RoomNotPostableException extends ChatException
{
    public function translationKey(): string
    {
        return 'chat.errors.room_not_postable';
    }
}
