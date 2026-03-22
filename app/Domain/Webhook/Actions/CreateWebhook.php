<?php

namespace App\Domain\Webhook\Actions;

use App\Domain\Webhook\Models\Webhook;

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
