<?php

use App\Domain\Chat\Broadcasting\ChatRoomChannel;
use App\Domain\Chat\Contracts\RoomPolicy;
use App\Domain\Chat\Models\ChatRoom;
use App\Domain\Chat\Services\ChatService;
use App\Domain\Chat\Services\PolicyResolver;
use App\Models\User;

class GrantingStubPolicy implements RoomPolicy
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
        return 'grants';
    }
}

class DenyingStubPolicy implements RoomPolicy
{
    public function canView(User $user, ChatRoom $room): bool
    {
        return false;
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
        return 'denies';
    }
}

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->service = app(ChatService::class);
    $this->channel = app(ChatRoomChannel::class);
});

it('grants subscription when the bound policy allows viewing', function (): void {
    $room = $this->service->ensureRoom('chat:test:allow', new GrantingStubPolicy);

    expect($this->channel->join($this->user, $room->id))->toBeTrue();
});

it('rejects subscription when the bound policy denies viewing', function (): void {
    $room = $this->service->ensureRoom('chat:test:deny', new DenyingStubPolicy);

    expect($this->channel->join($this->user, $room->id))->toBeFalse();
});

it('rejects subscription outright when the room is archived', function (): void {
    $room = $this->service->ensureRoom('chat:test:archived', new GrantingStubPolicy);
    $this->service->archive($room);

    expect($this->channel->join($this->user, $room->fresh()->id))->toBeFalse();
});

it('still allows subscription when the room is WriteLocked (read-only viewers)', function (): void {
    $room = $this->service->ensureRoom('chat:test:writelocked', new GrantingStubPolicy);
    $this->service->writeLock($room);

    expect($this->channel->join($this->user, $room->fresh()->id))->toBeTrue();
});

it('rejects subscription for unknown rooms', function (): void {
    expect($this->channel->join($this->user, 999999))->toBeFalse();
});

it('resolves the bound policy via PolicyResolver', function (): void {
    $room = $this->service->ensureRoom('chat:test:resolver', new GrantingStubPolicy);

    $resolved = app(PolicyResolver::class)->resolve($room->fresh());

    expect($resolved)->toBeInstanceOf(GrantingStubPolicy::class);
});
