<?php

namespace App\Domain\Integration\Events;

use App\Domain\Integration\Models\IntegrationApp;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IntegrationAccessed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly IntegrationApp $integrationApp,
    ) {}
}
