<?php

use App\Domain\Chat\Actions\CloseRoom;
use App\Domain\Chat\Actions\DeleteMessage;
use App\Domain\Chat\Actions\MuteUser;
use App\Domain\Chat\Actions\PostMessage;
use App\Domain\Chat\Actions\UnmuteUser;
use App\Domain\Chat\Contracts\RoomPolicy;
use App\Domain\Chat\Enums\ModerationAction;
use App\Domain\Chat\Enums\RoomStatus;
use App\Domain\Chat\Exceptions\ModerationUnauthorizedException;
use App\Domain\Chat\Models\ChatMessage;
use App\Domain\Chat\Models\ChatModerationAction;
use App\Domain\Chat\Models\ChatRoom;
use App\Domain\Chat\Models\ChatRoomMembership;
use App\Domain\Chat\Services\ChatService;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

class ModerationPolicyDenyAll implements RoomPolicy
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
        return 'deny-mod';
    }
}

class ModerationPolicyAllowAll implements RoomPolicy
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
        return true;
    }

    public function describe(ChatRoom $room): string
    {
        return 'allow-mod';
    }
}

beforeEach(function (): void {
    Role::updateOrCreate(['name' => RoleName::Moderator->value], ['label' => 'Moderator']);
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);

    $this->moderator = User::factory()->withRole(RoleName::Moderator)->create();
    $this->plainUser = User::factory()->withRole(RoleName::User)->create();
    $this->target = User::factory()->create();

    $this->service = app(ChatService::class);
});

it('allows a global moderator to mute a user even when the policy denies', function (): void {
    $room = $this->service->ensureRoom('mod:'.uniqid(), new ModerationPolicyDenyAll);

    $action = app(MuteUser::class)->execute(
        $this->moderator,
        $room,
        $this->target,
        now()->addMinutes(15),
        'spamming',
    );

    expect($action->action)->toBe(ModerationAction::Mute);
    expect($action->expires_at)->not->toBeNull();

    $membership = ChatRoomMembership::where('room_id', $room->id)
        ->where('user_id', $this->target->id)
        ->first();
    expect($membership->muted_until)->not->toBeNull();
});

it('rejects mute attempts by a user without ModerateChat and without policy grant', function (): void {
    $room = $this->service->ensureRoom('mod:'.uniqid(), new ModerationPolicyDenyAll);

    expect(fn () => app(MuteUser::class)->execute(
        $this->plainUser,
        $room,
        $this->target,
        now()->addMinutes(5),
    ))->toThrow(ModerationUnauthorizedException::class);
});

it('allows mute when the room policy grants moderate even without the global permission', function (): void {
    $room = $this->service->ensureRoom('mod:'.uniqid(), new ModerationPolicyAllowAll);

    $action = app(MuteUser::class)->execute(
        $this->plainUser,
        $room,
        $this->target,
        now()->addMinutes(5),
    );

    expect($action->action)->toBe(ModerationAction::Mute);
});

it('clears mute via UnmuteUser', function (): void {
    $room = $this->service->ensureRoom('mod:'.uniqid(), new ModerationPolicyDenyAll);

    app(MuteUser::class)->execute($this->moderator, $room, $this->target, now()->addHour());

    $action = app(UnmuteUser::class)->execute($this->moderator, $room, $this->target);

    expect($action->action)->toBe(ModerationAction::Unmute);
    $membership = ChatRoomMembership::where('room_id', $room->id)
        ->where('user_id', $this->target->id)
        ->first();
    expect($membership->muted_until)->toBeNull();
});

it('soft-deletes a message and attributes it to the moderator', function (): void {
    $room = $this->service->ensureRoom('mod:'.uniqid(), new ModerationPolicyAllowAll);
    $message = app(PostMessage::class)->execute($this->plainUser, $room, 'hello world');

    $action = app(DeleteMessage::class)->execute($this->moderator, $message);

    expect($action->action)->toBe(ModerationAction::DeleteMessage);
    expect(ChatMessage::withTrashed()->find($message->id)->trashed())->toBeTrue();
    expect(ChatMessage::withTrashed()->find($message->id)->deleted_by)->toBe($this->moderator->id);
});

it('preserves the raw body on soft-deleted messages (for GDPR export)', function (): void {
    $room = $this->service->ensureRoom('mod:'.uniqid(), new ModerationPolicyAllowAll);
    $message = app(PostMessage::class)->execute($this->plainUser, $room, 'sensitive content');

    app(DeleteMessage::class)->execute($this->moderator, $message);

    expect(ChatMessage::withTrashed()->find($message->id)->body)->toBe('sensitive content');
});

it('closes a room with CloseRoom and logs the action', function (): void {
    $room = $this->service->ensureRoom('mod:'.uniqid(), new ModerationPolicyDenyAll);

    $action = app(CloseRoom::class)->execute($this->moderator, $room, 'derailment');

    expect($action->action)->toBe(ModerationAction::CloseRoom);
    expect($room->fresh()->status)->toBe(RoomStatus::WriteLocked);
});

it('records every moderation action in the per-room audit log', function (): void {
    $room = $this->service->ensureRoom('mod:'.uniqid(), new ModerationPolicyDenyAll);

    app(MuteUser::class)->execute($this->moderator, $room, $this->target, now()->addMinutes(10));
    app(UnmuteUser::class)->execute($this->moderator, $room, $this->target);

    expect(
        ChatModerationAction::where('room_id', $room->id)->count(),
    )->toBe(2);
});

it('rejects mute attempts after the muted user is unmuted (mute is no longer in effect)', function (): void {
    $room = $this->service->ensureRoom('mod:'.uniqid(), new ModerationPolicyAllowAll);

    app(MuteUser::class)->execute(
        $this->moderator,
        $room,
        $this->target,
        now()->subSecond(),
    );

    // PostMessage should now succeed because muted_until is in the past.
    $message = app(PostMessage::class)->execute($this->target, $room, 'after expiry');

    expect($message->body)->toBe('after expiry');
});
