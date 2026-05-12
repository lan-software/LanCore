<?php

use App\Domain\Chat\Enums\RoomStatus;
use App\Domain\Chat\Models\ChatRoom;
use App\Domain\Chat\Models\ChatRoomMembership;
use App\Domain\Competition\Chat\CompetitionRoomPolicy;
use App\Domain\Competition\Enums\CompetitionStatus;
use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\CompetitionTeam;
use App\Domain\Competition\Models\CompetitionTeamMember;
use App\Models\User;

it('creates exactly one chat room when a competition is published (Draft → RegistrationOpen)', function (): void {
    $competition = Competition::factory()->create(['status' => CompetitionStatus::Draft]);

    $competition->update(['status' => CompetitionStatus::RegistrationOpen]);

    $rooms = ChatRoom::where('key', "competition:{$competition->id}")->get();
    expect($rooms)->toHaveCount(1);
    expect($rooms->first()->status)->toBe(RoomStatus::Open);
    expect($rooms->first()->consumer_domain)->toBe('Competition');
    expect($rooms->first()->policy_class)->toBe(CompetitionRoomPolicy::class);
});

it('re-publishing is idempotent — the same room row is reused', function (): void {
    $competition = Competition::factory()->create(['status' => CompetitionStatus::Draft]);
    $competition->update(['status' => CompetitionStatus::RegistrationOpen]);
    $first = ChatRoom::where('key', "competition:{$competition->id}")->first();

    // Simulate a re-emit (e.g. admin "re-publish" or webhook retry):
    $competition->update(['status' => CompetitionStatus::RegistrationClosed]);
    $competition->update(['status' => CompetitionStatus::RegistrationOpen]);

    expect(ChatRoom::where('key', "competition:{$competition->id}")->count())->toBe(1);
    expect(ChatRoom::where('key', "competition:{$competition->id}")->first()->id)->toBe($first->id);
});

it('write-locks the competition room when status transitions to Finished', function (): void {
    $competition = Competition::factory()->create(['status' => CompetitionStatus::Draft]);
    $competition->update(['status' => CompetitionStatus::RegistrationOpen]);
    $competition->update(['status' => CompetitionStatus::RegistrationClosed]);
    $competition->update(['status' => CompetitionStatus::Running]);

    $competition->update(['status' => CompetitionStatus::Finished]);

    $room = ChatRoom::where('key', "competition:{$competition->id}")->first();
    expect($room->status)->toBe(RoomStatus::WriteLocked);
});

it('archives the competition room when status transitions to Archived', function (): void {
    $competition = Competition::factory()->create(['status' => CompetitionStatus::Finished]);
    $competition->update(['status' => CompetitionStatus::Archived]);

    $room = ChatRoom::where('key', "competition:{$competition->id}")->first();
    expect($room->status)->toBe(RoomStatus::Archived);
});

it('auto-joins existing team members when the competition is published', function (): void {
    $competition = Competition::factory()->create(['status' => CompetitionStatus::Draft]);
    $captain = User::factory()->create();
    $member = User::factory()->create();
    $team = CompetitionTeam::factory()->create([
        'competition_id' => $competition->id,
        'captain_user_id' => $captain->id,
    ]);
    CompetitionTeamMember::create([
        'team_id' => $team->id,
        'user_id' => $captain->id,
        'joined_at' => now(),
    ]);
    CompetitionTeamMember::create([
        'team_id' => $team->id,
        'user_id' => $member->id,
        'joined_at' => now(),
    ]);

    $competition->update(['status' => CompetitionStatus::RegistrationOpen]);

    $room = ChatRoom::where('key', "competition:{$competition->id}")->first();
    $memberships = ChatRoomMembership::where('room_id', $room->id)->pluck('user_id')->all();
    expect($memberships)->toContain($captain->id)->toContain($member->id);
});

it('auto-joins users who join a team AFTER the competition is published', function (): void {
    $competition = Competition::factory()->create(['status' => CompetitionStatus::Draft]);
    $competition->update(['status' => CompetitionStatus::RegistrationOpen]);

    $captain = User::factory()->create();
    $team = CompetitionTeam::factory()->create([
        'competition_id' => $competition->id,
        'captain_user_id' => $captain->id,
    ]);
    CompetitionTeamMember::create([
        'team_id' => $team->id,
        'user_id' => $captain->id,
        'joined_at' => now(),
    ]);

    $room = ChatRoom::where('key', "competition:{$competition->id}")->first();
    expect(
        ChatRoomMembership::where('room_id', $room->id)->where('user_id', $captain->id)->exists(),
    )->toBeTrue();
});

it('CompetitionRoomPolicy::canView returns true only for confirmed participants', function (): void {
    $competition = Competition::factory()->create(['status' => CompetitionStatus::Draft]);
    $participant = User::factory()->create();
    $outsider = User::factory()->create();

    $team = CompetitionTeam::factory()->create([
        'competition_id' => $competition->id,
        'captain_user_id' => $participant->id,
    ]);
    CompetitionTeamMember::create([
        'team_id' => $team->id,
        'user_id' => $participant->id,
        'joined_at' => now(),
    ]);

    $competition->update(['status' => CompetitionStatus::RegistrationOpen]);
    $room = ChatRoom::where('key', "competition:{$competition->id}")->first();
    $policy = new CompetitionRoomPolicy;

    expect($policy->canView($participant, $room))->toBeTrue();
    expect($policy->canView($outsider, $room))->toBeFalse();
});
