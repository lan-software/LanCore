<?php

namespace App\Domain\Webhook\Actions;

use App\Domain\Webhook\Models\Webhook;

/**
 * @see docs/mil-std-498/SRS.md WHK-F-001
 */
class DeleteWebhook
{
    public function execute(Webhook $webhook): void
    {
        $webhook->delete();
    }
}
