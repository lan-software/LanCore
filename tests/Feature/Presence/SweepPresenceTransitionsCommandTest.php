<?php

use App\Domain\Chat\Models\ChatRoom;
use App\Domain\Chat\Models\ChatRoomMembership;
use App\Domain\Competition\Chat\CompetitionRoomPolicy;
use App\Domain\Presence\Enums\PresenceStatus;
use App\Domain\Presence\Services\PresenceTracker;
use App\Models\User;

it('does nothing when no chat-room memberships exist', function (): void {
    $this->mock(PresenceTracker::class, function ($mock): void {
        $mock->shouldReceive('bulkStatusFor')->never();
        $mock->shouldReceive('broadcastChange')->never();
    });

    $this->artisan('presence:sweep')->assertSuccessful();
});

it('broadcasts the derived status for every distinct chat-room member', function (): void {
    $room = ChatRoom::create([
        'key' => 'competition:probe',
        'title' => 'Probe',
        'consumer_domain' => 'Competition',
        'policy_class' => CompetitionRoomPolicy::class,
    ]);

    $alice = User::factory()->create();
    $bob = User::factory()->create();
    // Bob has two memberships to prove distinct() collapses to a single sweep.
    $room2 = ChatRoom::create([
        'key' => 'competition:probe-2',
        'title' => 'Probe 2',
        'consumer_domain' => 'Competition',
        'policy_class' => CompetitionRoomPolicy::class,
    ]);
    ChatRoomMembership::create(['room_id' => $room->id, 'user_id' => $alice->id, 'joined_at' => now()]);
    ChatRoomMembership::create(['room_id' => $room->id, 'user_id' => $bob->id, 'joined_at' => now()]);
    ChatRoomMembership::create(['room_id' => $room2->id, 'user_id' => $bob->id, 'joined_at' => now()]);

    $this->mock(PresenceTracker::class, function ($mock) use ($alice, $bob): void {
        $mock->shouldReceive('bulkStatusFor')
            ->once()
            ->andReturn([
                (string) $alice->id => PresenceStatus::Active,
                (string) $bob->id => PresenceStatus::Idle,
            ]);
        $mock->shouldReceive('broadcastChange')->with((string) $alice->id, PresenceStatus::Active)->once();
        $mock->shouldReceive('broadcastChange')->with((string) $bob->id, PresenceStatus::Idle)->once();
    });

    $this->artisan('presence:sweep')->assertSuccessful();
});
