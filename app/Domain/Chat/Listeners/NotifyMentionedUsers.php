<?php

namespace App\Domain\Chat\Listeners;

use App\Domain\Chat\Events\MessagePosted;
use App\Domain\Chat\Notifications\ChatMentionNotification;
use App\Domain\Chat\Services\PolicyResolver;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

/**
 * @see docs/mil-std-498/SRS.md CHT-F-014, CHT-F-015
 */
class NotifyMentionedUsers implements ShouldQueue
{
    public function __construct(private readonly PolicyResolver $resolver) {}

    public function handle(MessagePosted $event): void
    {
        $mentions = $event->message->mentions_json ?? [];

        if ($mentions === []) {
            return;
        }

        $authorId = $event->message->user_id;
        $room = $event->message->room;
        $policy = $this->resolver->resolve($room);

        $recipients = User::query()
            ->whereIn('id', $mentions)
            ->where('id', '!=', $authorId)
            ->with('notificationPreference')
            ->get()
            ->filter(fn (User $u) => $policy->canView($u, $room));

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new ChatMentionNotification($event->message));
    }
}
