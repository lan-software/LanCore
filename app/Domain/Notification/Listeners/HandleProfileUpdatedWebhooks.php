<?php

namespace App\Domain\Notification\Listeners;

use App\Domain\Notification\Events\ProfileUpdated;
use App\Domain\Profile\Enums\AvatarSource;
use App\Domain\Profile\Enums\ProfileVisibility;
use App\Domain\Webhook\Actions\DispatchWebhooks;
use App\Domain\Webhook\Enums\WebhookEvent;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * @see docs/mil-std-498/IRS.md IF-WHK ProfileUpdated payload
 * @see docs/mil-std-498/SRS.md ICLIB-F-002 (amended)
 */
class HandleProfileUpdatedWebhooks implements ShouldQueue
{
    public function __construct(private readonly DispatchWebhooks $dispatchWebhooks) {}

    public function handle(ProfileUpdated $event): void
    {
        $user = $event->user;

        $avatarSource = $user->avatar_source instanceof AvatarSource
            ? $user->avatar_source
            : (AvatarSource::tryFrom((string) $user->avatar_source) ?? AvatarSource::Default);
        $visibility = $user->profile_visibility instanceof ProfileVisibility
            ? $user->profile_visibility
            : (ProfileVisibility::tryFrom((string) $user->profile_visibility) ?? ProfileVisibility::LoggedIn);

        $this->dispatchWebhooks->execute(WebhookEvent::ProfileUpdated, [
            'event' => WebhookEvent::ProfileUpdated->value,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'email' => $user->email,
                'locale' => $user->locale,
                'avatar_url' => $user->avatarUrl(),
                'avatar_source' => $avatarSource->value,
                'short_bio' => $user->short_bio,
                'profile_emoji' => $user->profile_emoji,
                'profile_visibility' => $visibility->value,
                'profile_url' => $user->profileUrl(),
                'profile_updated_at' => $user->profile_updated_at?->toIso8601String(),
                'updated_at' => $user->updated_at?->toIso8601String(),
            ],
        ]);
    }
}
