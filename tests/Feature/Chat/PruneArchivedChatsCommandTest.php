<?php

use App\Domain\Chat\Contracts\RoomPolicy;
use App\Domain\Chat\Enums\ModerationAction;
use App\Domain\Chat\Models\ChatMessage;
use App\Domain\Chat\Models\ChatModerationAction;
use App\Domain\Chat\Models\ChatRoom;
use App\Domain\Chat\Services\ChatService;
use App\Domain\Competition\Enums\CompetitionStatus;
use App\Domain\Competition\Models\Competition;
use App\Models\User;

class PruneAllowAllPolicy implements RoomPolicy
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
        return 'open';
    }
}

beforeEach(function (): void {
    $this->service = app(ChatService::class);
});

it('refuses to run without --older-than', function (): void {
    $this->artisan('chat:prune-archived')
        ->expectsOutputToContain('--older-than')
        ->assertFailed();
});

it('refuses to run with a non-positive --older-than', function (): void {
    $this->artisan('chat:prune-archived', ['--older-than' => '0'])
        ->assertFailed();
});

it('does nothing when no archived competitions match', function (): void {
    $this->artisan('chat:prune-archived', ['--older-than' => 30])
        ->expectsOutputToContain('No archived competitions')
        ->assertSuccessful();
});

it('dry-run reports counts but deletes nothing', function (): void {
    $competition = Competition::factory()->create(['status' => CompetitionStatus::Archived]);
    $room = $this->service->ensureRoom("competition:{$competition->id}", new PruneAllowAllPolicy);
    $this->service->archive($room);
    $room->update(['archived_at' => now()->subDays(40)]);

    $user = User::factory()->create();
    ChatMessage::create([
        'room_id' => $room->id,
        'user_id' => $user->id,
        'body' => 'goodbye world',
    ]);

    $this->artisan('chat:prune-archived', ['--older-than' => 30, '--dry-run' => true])
        ->assertSuccessful();

    expect(ChatRoom::find($room->id))->not->toBeNull();
    expect(ChatMessage::where('room_id', $room->id)->count())->toBe(1);
});

it('deletes rooms beyond the threshold and their cascading rows', function (): void {
    $competition = Competition::factory()->create(['status' => CompetitionStatus::Archived]);
    $room = $this->service->ensureRoom("competition:{$competition->id}", new PruneAllowAllPolicy);
    $matchRoom = $this->service->ensureRoom(
        "competition:{$competition->id}:match:1",
        new PruneAllowAllPolicy,
    );
    $this->service->archive($room);
    $this->service->archive($matchRoom);
    $room->update(['archived_at' => now()->subDays(40)]);
    $matchRoom->update(['archived_at' => now()->subDays(40)]);

    $user = User::factory()->create();
    ChatMessage::create(['room_id' => $room->id, 'user_id' => $user->id, 'body' => 'a']);
    ChatModerationAction::create([
        'room_id' => $room->id,
        'actor_id' => $user->id,
        'action' => ModerationAction::CloseRoom,
    ]);

    $this->artisan('chat:prune-archived', ['--older-than' => 30])->assertSuccessful();

    expect(ChatRoom::find($room->id))->toBeNull();
    expect(ChatRoom::find($matchRoom->id))->toBeNull();
    expect(ChatMessage::withTrashed()->where('room_id', $room->id)->count())->toBe(0);
    expect(ChatModerationAction::where('room_id', $room->id)->count())->toBe(0);
});

it('skips rooms not yet beyond the threshold', function (): void {
    $competition = Competition::factory()->create(['status' => CompetitionStatus::Archived]);
    $room = $this->service->ensureRoom("competition:{$competition->id}", new PruneAllowAllPolicy);
    $this->service->archive($room);
    $room->update(['archived_at' => now()->subDays(10)]); // only 10 days ago

    $this->artisan('chat:prune-archived', ['--older-than' => 30])->assertSuccessful();

    expect(ChatRoom::find($room->id))->not->toBeNull();
});

it('skips rooms whose backing competition is not archived', function (): void {
    $competition = Competition::factory()->create(['status' => CompetitionStatus::Running]);
    $room = $this->service->ensureRoom("competition:{$competition->id}", new PruneAllowAllPolicy);
    $this->service->archive($room);
    $room->update(['archived_at' => now()->subDays(60)]);

    $this->artisan('chat:prune-archived', ['--older-than' => 30])->assertSuccessful();

    expect(ChatRoom::find($room->id))->not->toBeNull();
});

it('scopes to a specific competition via --competition', function (): void {
    $a = Competition::factory()->create(['status' => CompetitionStatus::Archived]);
    $b = Competition::factory()->create(['status' => CompetitionStatus::Archived]);
    $roomA = $this->service->ensureRoom("competition:{$a->id}", new PruneAllowAllPolicy);
    $roomB = $this->service->ensureRoom("competition:{$b->id}", new PruneAllowAllPolicy);
    $this->service->archive($roomA);
    $this->service->archive($roomB);
    $roomA->update(['archived_at' => now()->subDays(40)]);
    $roomB->update(['archived_at' => now()->subDays(40)]);

    $this->artisan('chat:prune-archived', ['--older-than' => 30, '--competition' => $a->id])
        ->assertSuccessful();

    expect(ChatRoom::find($roomA->id))->toBeNull();
    expect(ChatRoom::find($roomB->id))->not->toBeNull();
});
