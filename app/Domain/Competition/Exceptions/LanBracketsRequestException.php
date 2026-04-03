<?php

namespace App\Domain\Competition\Exceptions;

use RuntimeException;

class LanBracketsRequestException extends RuntimeException
{
    public function __construct(string $message = 'LanBrackets request failed.', int $code = 0)
    {
        parent::__construct($message, $code);
    }
}
