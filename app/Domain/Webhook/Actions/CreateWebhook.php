<?php

namespace App\Domain\Webhook\Actions;

use App\Domain\Webhook\Models\Webhook;

/**
 * @see docs/mil-std-498/SSS.md CAP-WHK-001
 * @see docs/mil-std-498/SRS.md WHK-F-001
 */
class CreateWebhook
{
    /**
     * @param  array{name: string, url: string, event: string, secret?: string|null, description?: string|null, is_active?: bool}  $attributes
     */
    public function execute(array $attributes): Webhook
    {
        return Webhook::create($attributes);
    }
}
