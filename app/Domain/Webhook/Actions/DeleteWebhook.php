<?php

namespace App\Domain\Webhook\Actions;

use App\Domain\Webhook\Models\Webhook;

class DeleteWebhook
{
    public function execute(Webhook $webhook): void
    {
        $webhook->delete();
    }
}
