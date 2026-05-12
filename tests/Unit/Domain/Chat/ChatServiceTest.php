<?php

use App\Domain\Chat\Contracts\RoomPolicy;
use App\Domain\Chat\Enums\RoomStatus;
use App\Domain\Chat\Models\ChatRoom;
use App\Domain\Chat\Services\ChatService;
use App\Models\User;

beforeEach(function (): void {
    $this->service = app(ChatService::class);
    $this->policy = new class implements RoomPolicy
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
            return 'stub';
        }
    };
});

it('creates a room with the bound policy class on first call', function (): void {
    $room = $this->service->ensureRoom('competition:1:match:42', $this->policy, [
        'consumer_domain' => 'Competition',
    ]);

    expect($room->status)->toBe(RoomStatus::Open);
    expect($room->policy_class)->toBe($this->policy::class);
    expect($room->consumer_domain)->toBe('Competition');
});

it('is idempotent — subsequent ensureRoom calls return the same row', function (): void {
    $first = $this->service->ensureRoom('competition:1:match:43', $this->policy);
    $second = $this->service->ensureRoom('competition:1:match:43', $this->policy);

    expect($second->id)->toBe($first->id);
    expect(ChatRoom::where('key', 'competition:1:match:43')->count())->toBe(1);
});

it('writeLock transitions Open → WriteLocked', function (): void {
    $room = $this->service->ensureRoom('competition:1:match:44', $this->policy);

    $this->service->writeLock($room);

    expect($room->fresh()->status)->toBe(RoomStatus::WriteLocked);
    expect($room->fresh()->write_locked_at)->not->toBeNull();
});

it('writeLock is a no-op when already WriteLocked', function (): void {
    $room = $this->service->ensureRoom('competition:1:match:45', $this->policy);
    $this->service->writeLock($room);
    $first = $room->fresh()->write_locked_at;

    sleep(1);
    $this->service->writeLock($room->fresh());

    expect($room->fresh()->write_locked_at->equalTo($first))->toBeTrue();
});

it('archive transitions any state → Archived', function (): void {
    $room = $this->service->ensureRoom('competition:1:match:46', $this->policy);

    $this->service->archive($room);

    expect($room->fresh()->status)->toBe(RoomStatus::Archived);
    expect($room->fresh()->archived_at)->not->toBeNull();
});

it('writeLock will not unarchive an archived room', function (): void {
    $room = $this->service->ensureRoom('competition:1:match:47', $this->policy);
    $this->service->archive($room);

    $this->service->writeLock($room->fresh());

    expect($room->fresh()->status)->toBe(RoomStatus::Archived);
});
