<?php

use App\Domain\Chat\Actions\PostMessage;
use App\Domain\Chat\Contracts\RoomPolicy;
use App\Domain\Chat\Events\MessagePosted;
use App\Domain\Chat\Exceptions\DuplicateMessageException;
use App\Domain\Chat\Exceptions\PostUnauthorizedException;
use App\Domain\Chat\Exceptions\RateLimitExceededException;
use App\Domain\Chat\Exceptions\RoomNotPostableException;
use App\Domain\Chat\Exceptions\UserMutedException;
use App\Domain\Chat\Models\ChatMessage;
use App\Domain\Chat\Models\ChatRoom;
use App\Domain\Chat\Models\ChatRoomMembership;
use App\Domain\Chat\Services\ChatService;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;

class AlwaysAllowPolicy implements RoomPolicy
{
    public function canView(User $user, ChatRoom $room): bool
    {
        return true;
    }

    public function canPost(User $user, ChatRoom $room): bool
    {
        return true;
    }

    public function canModerate(User $user, ChatRoom $room): bool
    {
        return false;
    }

    public function describe(ChatRoom $room): string
    {
        return 'open';
    }
}

class AlwaysDenyPostPolicy implements RoomPolicy
{
    public function canView(User $user, ChatRoom $room): bool
    {
        return true;
    }

    public function canPost(User $user, ChatRoom $room): bool
    {
        return false;
    }

    public function canModerate(User $user, ChatRoom $room): bool
    {
        return false;
    }

    public function describe(ChatRoom $room): string
    {
        return 'no-post';
    }
}

beforeEach(function (): void {
    Config::set('chat.rate_limits.short.max', 5);
    Config::set('chat.rate_limits.short.decay_seconds', 10);
    Config::set('chat.rate_limits.long.max', 30);
    Config::set('chat.rate_limits.long.decay_seconds', 60);
    Config::set('chat.duplicate_window', 10);

    $this->user = User::factory()->create();
    $this->service = app(ChatService::class);
    $this->action = app(PostMessage::class);
    $this->room = $this->service->ensureRoom('post-test:'.uniqid(), new AlwaysAllowPolicy);

    RateLimiter::clear("chat:post:short:{$this->user->id}");
    RateLimiter::clear("chat:post:long:{$this->user->id}");
});

it('persists the message and dispatches MessagePosted on success', function (): void {
    Event::fake([MessagePosted::class]);

    $message = $this->action->execute($this->user, $this->room, 'hello world');

    expect($message)->toBeInstanceOf(ChatMessage::class);
    expect($message->body)->toBe('hello world');
    expect($message->room_id)->toBe($this->room->id);

    Event::assertDispatched(
        MessagePosted::class,
        fn (MessagePosted $e) => $e->message->id === $message->id,
    );
});

it('normalizes whitespace in the body before persisting', function (): void {
    Event::fake([MessagePosted::class]);

    $message = $this->action->execute($this->user, $this->room, "  hello\n\n  world  ");

    expect($message->body)->toBe('hello world');
});

it('rejects empty bodies', function (): void {
    expect(fn () => $this->action->execute($this->user, $this->room, "   \t  "))
        ->toThrow(InvalidArgumentException::class);
});

it('rejects posts to a WriteLocked room with a translated key', function (): void {
    $this->service->writeLock($this->room);

    expect(fn () => $this->action->execute($this->user, $this->room->fresh(), 'hi'))
        ->toThrow(RoomNotPostableException::class);
});

it('rejects posts to an Archived room with a translated key', function (): void {
    $this->service->archive($this->room);

    expect(fn () => $this->action->execute($this->user, $this->room->fresh(), 'hi'))
        ->toThrow(RoomNotPostableException::class);
});

it('rejects posts when the policy denies canPost', function (): void {
    $room = $this->service->ensureRoom('post-test:deny:'.uniqid(), new AlwaysDenyPostPolicy);

    expect(fn () => $this->action->execute($this->user, $room, 'hi'))
        ->toThrow(PostUnauthorizedException::class);
});

it('rejects posts from a user muted in the room', function (): void {
    ChatRoomMembership::create([
        'room_id' => $this->room->id,
        'user_id' => $this->user->id,
        'joined_at' => now(),
        'muted_until' => now()->addMinutes(5),
    ]);

    expect(fn () => $this->action->execute($this->user, $this->room, 'hi'))
        ->toThrow(UserMutedException::class);
});

it('allows posts after the mute expires', function (): void {
    $membership = ChatRoomMembership::create([
        'room_id' => $this->room->id,
        'user_id' => $this->user->id,
        'joined_at' => now(),
        'muted_until' => now()->subMinutes(1),
    ]);

    expect(fn () => $this->action->execute($this->user, $this->room, 'hi after mute'))
        ->not->toThrow(UserMutedException::class);
});

it('rejects bursts beyond the short rate limit', function (): void {
    for ($i = 1; $i <= 5; $i++) {
        $this->action->execute($this->user, $this->room, "msg {$i}");
    }

    expect(fn () => $this->action->execute($this->user, $this->room, 'over limit'))
        ->toThrow(
            RateLimitExceededException::class,
            'Chat rate limit exceeded (short window).',
        );
});

it('rejects exact duplicates within the duplicate window', function (): void {
    $this->action->execute($this->user, $this->room, 'same exact text');

    expect(fn () => $this->action->execute($this->user, $this->room, 'same exact text'))
        ->toThrow(DuplicateMessageException::class);
});

it('allows the same body outside the duplicate window', function (): void {
    $this->action->execute($this->user, $this->room, 'echo');

    // Travel forward past the window.
    $this->travel(15)->seconds();

    // Reset rate-limit so we don't trip the short cap.
    RateLimiter::clear("chat:post:short:{$this->user->id}");

    expect(fn () => $this->action->execute($this->user, $this->room->fresh(), 'echo'))
        ->not->toThrow(DuplicateMessageException::class);
});
