<?php

namespace App\Domain\Chat\Actions;

use App\Domain\Chat\Enums\RoomStatus;
use App\Domain\Chat\Events\MessagePosted;
use App\Domain\Chat\Exceptions\DuplicateMessageException;
use App\Domain\Chat\Exceptions\PostUnauthorizedException;
use App\Domain\Chat\Exceptions\RateLimitExceededException;
use App\Domain\Chat\Exceptions\RoomNotPostableException;
use App\Domain\Chat\Exceptions\UserMutedException;
use App\Domain\Chat\Models\ChatMessage;
use App\Domain\Chat\Models\ChatRoom;
use App\Domain\Chat\Models\ChatRoomMembership;
use App\Domain\Chat\Services\PolicyResolver;
use App\Domain\Chat\Support\MentionParser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * @see docs/mil-std-498/SRS.md CHT-F-012, CHT-F-019..025
 */
class PostMessage
{
    public function __construct(
        private readonly PolicyResolver $policyResolver,
        private readonly MentionParser $mentionParser,
    ) {}

    public function execute(User $user, ChatRoom $room, string $body): ChatMessage
    {
        $body = Str::squish($body);

        if ($body === '') {
            throw new \InvalidArgumentException('Empty message body.');
        }

        if ($room->status === RoomStatus::Archived || $room->status === RoomStatus::WriteLocked) {
            throw new RoomNotPostableException;
        }

        $policy = $this->policyResolver->resolve($room);

        if (! $policy->canPost($user, $room)) {
            throw new PostUnauthorizedException;
        }

        $this->enforceMute($user, $room);
        $this->enforceRateLimits($user);
        $this->rejectDuplicate($user, $room, $body);

        $mentions = $this->mentionParser->parse($body);

        $message = DB::transaction(fn () => ChatMessage::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'body' => $body,
            'mentions_json' => $mentions,
        ]));

        MessagePosted::dispatch($message);

        return $message;
    }

    private function enforceMute(User $user, ChatRoom $room): void
    {
        $membership = ChatRoomMembership::query()
            ->where('room_id', $room->id)
            ->where('user_id', $user->id)
            ->first();

        if ($membership === null) {
            return;
        }

        if ($membership->muted_until !== null && $membership->muted_until->isFuture()) {
            throw new UserMutedException;
        }
    }

    private function enforceRateLimits(User $user): void
    {
        $shortMax = (int) config('chat.rate_limits.short.max', 5);
        $shortDecay = (int) config('chat.rate_limits.short.decay_seconds', 10);
        $longMax = (int) config('chat.rate_limits.long.max', 30);
        $longDecay = (int) config('chat.rate_limits.long.decay_seconds', 60);

        $shortKey = "chat:post:short:{$user->id}";
        $longKey = "chat:post:long:{$user->id}";

        if (RateLimiter::tooManyAttempts($shortKey, $shortMax)) {
            throw new RateLimitExceededException('short');
        }

        if (RateLimiter::tooManyAttempts($longKey, $longMax)) {
            throw new RateLimitExceededException('long');
        }

        RateLimiter::hit($shortKey, $shortDecay);
        RateLimiter::hit($longKey, $longDecay);
    }

    private function rejectDuplicate(User $user, ChatRoom $room, string $body): void
    {
        $window = (int) config('chat.duplicate_window', 10);

        $exists = ChatMessage::query()
            ->where('room_id', $room->id)
            ->where('user_id', $user->id)
            ->where('body', $body)
            ->where('created_at', '>=', now()->subSeconds($window))
            ->exists();

        if ($exists) {
            throw new DuplicateMessageException;
        }
    }
}
