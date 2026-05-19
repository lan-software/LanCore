<?php

use App\Domain\Chat\Enums\RoomStatus;
use App\Domain\Chat\Models\ChatRoom;
use App\Domain\Chat\Models\ChatRoomMembership;
use App\Domain\Competition\Chat\MatchRoomPolicy;
use App\Domain\Competition\Enums\CompetitionStatus;
use App\Domain\Competition\Enums\MatchFinalizationSource;
use App\Domain\Competition\Events\MatchFinalized;
use App\Domain\Competition\Events\MatchReadyForOrchestration;
use App\Domain\Competition\Listeners\EnsureMatchRoomOnReady;
use App\Domain\Competition\Listeners\WriteLockMatchRoomOnFinalized;
use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\CompetitionTeam;
use App\Domain\Competition\Models\CompetitionTeamMember;
use App\Models\User;
use Illuminate\Support\Facades\Event;

/**
 * Tests bypass the orchestration dispatcher (which writes to `orchestration_jobs`
 * requiring `game_id`) and invoke the chat listener directly. The orchestration
 * pipeline is unrelated to this ticket and tested separately.
 */
function fireMatchReady(Competition $competition, string $matchId, array $matchData): void
{
    $event = new MatchReadyForOrchestration($competition, $matchId, $matchData);
    app(EnsureMatchRoomOnReady::class)->handle($event);
}

function fireMatchFinalized(Competition $competition, string $matchId, MatchFinalizationSource $source): void
{
    $event = new MatchFinalized($competition, $matchId, $source);
    app(WriteLockMatchRoomOnFinalized::class)->handle($event);
}

it('creates a match room when the ready listener fires', function (): void {
    $competition = Competition::factory()->create();

    fireMatchReady($competition, 42, ['id' => 42, 'participants' => []]);

    $room = ChatRoom::where('key', "competition:{$competition->id}:match:42")->first();
    expect($room)->not->toBeNull();
    expect($room->status)->toBe(RoomStatus::Open);
    expect($room->policy_class)->toBe(MatchRoomPolicy::class);
});

it('is idempotent — re-emits do not duplicate the match room', function (): void {
    $competition = Competition::factory()->create();

    fireMatchReady($competition, 43, ['id' => 43, 'participants' => []]);
    fireMatchReady($competition, 43, ['id' => 43, 'participants' => []]);
    fireMatchReady($competition, 43, ['id' => 43, 'participants' => []]);

    expect(ChatRoom::where('key', "competition:{$competition->id}:match:43")->count())->toBe(1);
});

it('auto-joins all team members of the participating teams', function (): void {
    $competition = Competition::factory()->create();
    $captain = User::factory()->create();
    $member = User::factory()->create();
    $team = CompetitionTeam::factory()->create([
        'competition_id' => $competition->id,
        'captain_user_id' => $captain->id,
    ]);
    CompetitionTeamMember::create(['team_id' => $team->id, 'user_id' => $captain->id, 'joined_at' => now()]);
    CompetitionTeamMember::create(['team_id' => $team->id, 'user_id' => $member->id, 'joined_at' => now()]);

    fireMatchReady($competition, 44, [
        'id' => 44,
        'participants' => [['competition_participant_id' => $team->id]],
    ]);

    $room = ChatRoom::where('key', "competition:{$competition->id}:match:44")->first();
    $memberships = ChatRoomMembership::where('room_id', $room->id)->pluck('user_id')->all();

    expect($memberships)->toContain($captain->id)->toContain($member->id);
});

it('write-locks the match room when MatchFinalized fires', function (): void {
    $competition = Competition::factory()->create();
    fireMatchReady($competition, 45, ['id' => 45, 'participants' => []]);

    fireMatchFinalized($competition, 45, MatchFinalizationSource::SubmittedByParticipants);

    $room = ChatRoom::where('key', "competition:{$competition->id}:match:45")->first();
    expect($room->status)->toBe(RoomStatus::WriteLocked);
});

it('does not crash on MatchFinalized for a match without a room', function (): void {
    $competition = Competition::factory()->create();

    expect(fn () => fireMatchFinalized(
        $competition,
        9999,
        MatchFinalizationSource::SubmittedByParticipants,
    ))->not->toThrow(Throwable::class);
});

it('cascades archive from competition to all match rooms', function (): void {
    $competition = Competition::factory()->create(['status' => CompetitionStatus::Finished]);
    fireMatchReady($competition, 46, ['id' => 46, 'participants' => []]);
    fireMatchReady($competition, 47, ['id' => 47, 'participants' => []]);

    $competition->update(['status' => CompetitionStatus::Archived]);

    $rooms = ChatRoom::where('key', 'like', "competition:{$competition->id}:match:%")->get();
    expect($rooms->pluck('status')->unique()->all())->toBe([RoomStatus::Archived]);
});

it('canView returns true only for users with a membership row', function (): void {
    $competition = Competition::factory()->create();
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

    fireMatchReady($competition, 48, [
        'id' => 48,
        'participants' => [['competition_participant_id' => $team->id]],
    ]);

    $room = ChatRoom::where('key', "competition:{$competition->id}:match:48")->first();
    $policy = new MatchRoomPolicy;

    expect($policy->canView($participant, $room))->toBeTrue();
    expect($policy->canView($outsider, $room))->toBeFalse();
});

it('is registered as a listener on MatchReadyForOrchestration in AppServiceProvider', function (): void {
    expect(Event::hasListeners(MatchReadyForOrchestration::class))
        ->toBeTrue();
});

it('is registered as a listener on MatchFinalized in AppServiceProvider', function (): void {
    expect(Event::hasListeners(MatchFinalized::class))->toBeTrue();
});
