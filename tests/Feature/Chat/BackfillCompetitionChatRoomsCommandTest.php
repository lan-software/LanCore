<?php

use App\Domain\Chat\Enums\RoomStatus;
use App\Domain\Chat\Models\ChatRoom;
use App\Domain\Chat\Models\ChatRoomMembership;
use App\Domain\Competition\Enums\CompetitionStatus;
use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\CompetitionTeam;
use App\Domain\Competition\Models\CompetitionTeamMember;
use App\Models\User;

it('creates a room and joins active team members for a published competition', function (): void {
    $competition = Competition::factory()->create(['status' => CompetitionStatus::Published]);
    $team = CompetitionTeam::factory()->create(['competition_id' => $competition->id]);

    $active = User::factory()->create();
    $left = User::factory()->create();
    CompetitionTeamMember::create(['team_id' => $team->id, 'user_id' => $active->id, 'joined_at' => now()]);
    CompetitionTeamMember::create(['team_id' => $team->id, 'user_id' => $left->id, 'joined_at' => now()->subDay(), 'left_at' => now()]);

    $this->artisan('chat:backfill-competition-rooms')
        ->expectsOutputToContain('created 1 new rooms')
        ->assertSuccessful();

    $room = ChatRoom::where('key', "competition:{$competition->id}")->first();
    expect($room)->not->toBeNull()
        ->and($room->consumer_domain)->toBe('Competition')
        ->and($room->status)->toBe(RoomStatus::Open)
        ->and(ChatRoomMembership::where('room_id', $room->id)->where('user_id', $active->id)->exists())->toBeTrue()
        ->and(ChatRoomMembership::where('room_id', $room->id)->where('user_id', $left->id)->exists())->toBeFalse();
});

it('skips draft competitions', function (): void {
    $competition = Competition::factory()->create(['status' => CompetitionStatus::Draft]);

    $this->artisan('chat:backfill-competition-rooms')
        ->expectsOutputToContain('Touched 0 competitions')
        ->assertSuccessful();

    expect(ChatRoom::where('key', "competition:{$competition->id}")->exists())->toBeFalse();
});

it('write-locks finished competition rooms and archives archived ones', function (): void {
    $finished = Competition::factory()->create(['status' => CompetitionStatus::Finished]);
    $archived = Competition::factory()->create(['status' => CompetitionStatus::Archived]);

    $this->artisan('chat:backfill-competition-rooms')->assertSuccessful();

    expect(ChatRoom::where('key', "competition:{$finished->id}")->first()->status)->toBe(RoomStatus::WriteLocked)
        ->and(ChatRoom::where('key', "competition:{$archived->id}")->first()->status)->toBe(RoomStatus::Archived);
});

it('is idempotent — a second run creates no new rooms', function (): void {
    Competition::factory()->create(['status' => CompetitionStatus::Published]);

    $this->artisan('chat:backfill-competition-rooms')
        ->expectsOutputToContain('created 1 new rooms')
        ->assertSuccessful();

    $this->artisan('chat:backfill-competition-rooms')
        ->expectsOutputToContain('created 0 new rooms')
        ->assertSuccessful();
});
