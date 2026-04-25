<?php

namespace App\Domain\Integration\Exceptions;

use RuntimeException;

class IntegrationConfigurationException extends RuntimeException
{
    public static function missingToken(string $slug): self
    {
        return new self("Missing integration token for slug '{$slug}'. Set the corresponding *_LANCORE_TOKEN env var or rerun with --allow-missing-tokens to seed without authentication.");
    }
}
