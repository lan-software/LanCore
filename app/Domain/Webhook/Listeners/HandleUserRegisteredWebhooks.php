<?php

namespace App\Domain\Webhook\Listeners;

use App\Domain\Profile\Enums\AvatarSource;
use App\Domain\Profile\Enums\ProfileVisibility;
use App\Domain\Webhook\Actions\DispatchWebhooks;
use App\Domain\Webhook\Enums\WebhookEvent;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * @see docs/mil-std-498/IRS.md IF-WHK UserRegistered payload
 */
class HandleUserRegisteredWebhooks implements ShouldQueue
{
    public function __construct(private readonly DispatchWebhooks $dispatchWebhooks) {}

    public function handle(Registered $event): void
    {
        /** @var User $user */
        $user = $event->user;

        $avatarSource = $user->avatar_source instanceof AvatarSource
            ? $user->avatar_source
            : (AvatarSource::tryFrom((string) $user->avatar_source) ?? AvatarSource::Default);
        $visibility = $user->profile_visibility instanceof ProfileVisibility
            ? $user->profile_visibility
            : (ProfileVisibility::tryFrom((string) $user->profile_visibility) ?? ProfileVisibility::LoggedIn);

        $this->dispatchWebhooks->execute(WebhookEvent::UserRegistered, [
            'event' => WebhookEvent::UserRegistered->value,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'email' => $user->email,
                'locale' => $user->locale,
                'avatar_url' => $user->avatarUrl(),
                'avatar_source' => $avatarSource->value,
                'profile_visibility' => $visibility->value,
                'profile_url' => $user->profileUrl(),
                'created_at' => $user->created_at?->toIso8601String(),
            ],
        ]);
    }
}
