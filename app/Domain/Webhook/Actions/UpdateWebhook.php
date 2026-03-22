<?php

namespace App\Domain\Webhook\Actions;

use App\Domain\Webhook\Models\Webhook;

class UpdateWebhook
{
    /**
     * @param  array{name?: string, url?: string, event?: string, secret?: string|null, description?: string|null, is_active?: bool}  $attributes
     */
    public function execute(Webhook $webhook, array $attributes): Webhook
    {
        $webhook->update($attributes);

        return $webhook;
    }
}
