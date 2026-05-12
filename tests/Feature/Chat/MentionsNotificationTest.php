<?php

use App\Domain\Chat\Actions\PostMessage;
use App\Domain\Chat\Contracts\RoomPolicy;
use App\Domain\Chat\Events\MessagePosted;
use App\Domain\Chat\Listeners\NotifyMentionedUsers;
use App\Domain\Chat\Models\ChatRoom;
use App\Domain\Chat\Notifications\ChatMentionNotification;
use App\Domain\Chat\Services\ChatService;
use App\Domain\Chat\Services\PolicyResolver;
use App\Domain\Notification\Channels\WebPushChannel;
use App\Domain\Notification\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

class MentionsAllowAllPolicy implements RoomPolicy
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

class MentionsAuthorOnlyPolicy implements RoomPolicy
{
    public function __construct(public readonly int $authorId) {}

    public function canView(User $user, ChatRoom $room): bool
    {
        return $user->id === $this->authorId;
    }

    public function canPost(User $user, ChatRoom $room): bool
    {
        return $user->id === $this->authorId;
    }

    public function canModerate(User $user, ChatRoom $room): bool
    {
        return false;
    }

    public function describe(ChatRoom $room): string
    {
        return 'author-only';
    }
}

beforeEach(function (): void {
    $this->author = User::factory()->create(['username' => 'author']);
    $this->mentioned = User::factory()->create(['username' => 'cita']);
    $this->service = app(ChatService::class);
    $this->action = app(PostMessage::class);
    $this->listener = app(NotifyMentionedUsers::class);
});

it('persists resolved mention ids on the message', function (): void {
    $room = $this->service->ensureRoom('mentions:'.uniqid(), new MentionsAllowAllPolicy);
    $message = $this->action->execute($this->author, $room, 'hey @cita');

    expect($message->mentions_json)->toBe([$this->mentioned->id]);
});

it('notifies mentioned users when the listener handles MessagePosted', function (): void {
    Notification::fake();

    $room = $this->service->ensureRoom('mentions:'.uniqid(), new MentionsAllowAllPolicy);
    $message = $this->action->execute($this->author, $room, 'hey @cita');

    $this->listener->handle(new MessagePosted($message));

    Notification::assertSentTo($this->mentioned, ChatMentionNotification::class);
});

it('does not notify the author for self-mentions', function (): void {
    Notification::fake();

    $room = $this->service->ensureRoom('mentions:'.uniqid(), new MentionsAllowAllPolicy);
    $message = $this->action->execute($this->author, $room, 'hey @author and @cita');

    $this->listener->handle(new MessagePosted($message));

    Notification::assertNotSentTo($this->author, ChatMentionNotification::class);
    Notification::assertSentTo($this->mentioned, ChatMentionNotification::class);
});

it('does not notify mentioned users who cannot view the room', function (): void {
    Notification::fake();

    $authorId = $this->author->id;
    app(PolicyResolver::class)->register(
        MentionsAuthorOnlyPolicy::class,
        fn () => new MentionsAuthorOnlyPolicy($authorId),
    );

    $room = $this->service->ensureRoom(
        'mentions:'.uniqid(),
        new MentionsAuthorOnlyPolicy($authorId),
    );
    $message = $this->action->execute($this->author, $room, 'hey @cita');

    $this->listener->handle(new MessagePosted($message));

    Notification::assertNothingSent();
});

it('mail channel is included when the user opts in (default true)', function (): void {
    NotificationPreference::factory()->for($this->mentioned)->create([
        'mail_on_chat_mention' => true,
        'push_on_chat_mention' => false,
    ]);

    $room = $this->service->ensureRoom('mentions:'.uniqid(), new MentionsAllowAllPolicy);
    $message = $this->action->execute($this->author, $room, 'hey @cita');

    $notification = new ChatMentionNotification($message);
    $channels = $notification->via($this->mentioned->fresh());

    expect($channels)->toContain('database')->toContain('mail');
    expect($channels)->not->toContain(WebPushChannel::class);
});

it('omits mail channel when the user opts out', function (): void {
    NotificationPreference::factory()->for($this->mentioned)->create([
        'mail_on_chat_mention' => false,
        'push_on_chat_mention' => true,
    ]);

    $room = $this->service->ensureRoom('mentions:'.uniqid(), new MentionsAllowAllPolicy);
    $message = $this->action->execute($this->author, $room, 'hey @cita');

    $channels = (new ChatMentionNotification($message))->via($this->mentioned->fresh());

    expect($channels)
        ->toContain('database')
        ->toContain(WebPushChannel::class)
        ->not->toContain('mail');
});
