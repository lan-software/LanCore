<?php

namespace App\Domain\Competition\Exceptions;

use RuntimeException;

class LanBracketsDisabledException extends RuntimeException
{
    public function __construct(string $message = 'LanBrackets integration is disabled.')
    {
        parent::__construct($message);
    }
}
