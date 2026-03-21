<?php

namespace App\Domain\Shop\Contracts;

use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class PaymentResult
{
    private function __construct(
        public readonly bool $requiresRedirect,
        public readonly ?string $redirectUrl = null,
        public readonly ?RedirectResponse $redirect = null,
        public readonly ?Response $inertiaResponse = null,
    ) {}

    /**
     * Create a result that redirects to an external payment provider.
     */
    public static function redirect(RedirectResponse $redirect): self
    {
        return new self(
            requiresRedirect: true,
            redirect: $redirect,
        );
    }

    /**
     * Create a result that immediately completes (no external redirect needed).
     */
    public static function completed(RedirectResponse $redirect): self
    {
        return new self(
            requiresRedirect: false,
            redirect: $redirect,
        );
    }

    public function toResponse(): RedirectResponse
    {
        return $this->redirect;
    }
}
