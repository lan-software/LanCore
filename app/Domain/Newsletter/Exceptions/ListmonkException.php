<?php

namespace App\Domain\Newsletter\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Raised by `ListmonkClient` when a call to the Listmonk REST API either
 * fails the network round-trip after retries (`unreachable`), receives an
 * authentication failure (`auth_failed`), or returns a non-2xx response
 * (`api_error`). The connectivity-test endpoints inspect `$kind` to map
 * back to the standard `{status, error?}` JSON contract used across the
 * External API admin page.
 *
 * @see docs/mil-std-498/SDD.md §5.12
 */
class ListmonkException extends RuntimeException
{
    public const KIND_NOT_CONFIGURED = 'not_configured';

    public const KIND_UNREACHABLE = 'unreachable';

    public const KIND_AUTH_FAILED = 'auth_failed';

    public const KIND_API_ERROR = 'api_error';

    public function __construct(
        string $message,
        public readonly string $kind = self::KIND_API_ERROR,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
