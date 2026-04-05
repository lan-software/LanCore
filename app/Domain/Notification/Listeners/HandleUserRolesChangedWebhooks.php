<?php

namespace App\Domain\Notification\Listeners;

use App\Domain\Notification\Events\UserRolesChanged;
use App\Domain\Webhook\Actions\DispatchWebhooks;
use App\Domain\Webhook\Enums\WebhookEvent;
use App\Enums\RoleName;
use Illuminate\Contracts\Queue\ShouldQueue;

class HandleUserRolesChangedWebhooks implements ShouldQueue
{
    public function __construct(private readonly DispatchWebhooks $dispatchWebhooks) {}

    public function handle(UserRolesChanged $event): void
    {
        $user = $event->user;
        $user->load('roles');

        $this->dispatchWebhooks->execute(WebhookEvent::UserRolesUpdated, [
            'event' => WebhookEvent::UserRolesUpdated->value,
            'user' => [
                'id' => $user->id,
                'username' => $user->name,
                'roles' => $user->roles->pluck('name')->map(fn (RoleName $role) => $role->value)->values()->all(),
            ],
            'changes' => [
                'added' => array_map(fn (RoleName $role) => $role->value, $event->addedRoles),
                'removed' => array_map(fn (RoleName $role) => $role->value, $event->removedRoles),
            ],
        ]);
    }
}
