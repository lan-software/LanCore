<?php

use App\Domain\Competition\Enums\MatchFinalizationSource;
use App\Domain\Competition\Events\MatchCompleted;
use App\Domain\Competition\Events\MatchFinalized;
use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\MatchResultProof;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

function dispatchMatchResultWebhook(Competition $competition, string $matchId, bool $forcedByAdmin = false): void
{
    $payload = [
        'event' => 'match.result_reported',
        'timestamp' => now()->toISOString(),
        'data' => [
            'match' => [
                'id' => $matchId,
                'external_reference_id' => (string) $competition->id,
                'forced_by_admin' => $forcedByAdmin,
                'status' => 'completed',
                'participants' => [],
            ],
        ],
    ];

    $signature = hash_hmac('sha256', json_encode($payload), config('lanbrackets.webhook_secret', ''));

    test()->postJson('/webhooks/lanbrackets', $payload, [
        'X-LanBrackets-Signature' => $signature,
        'X-LanBrackets-Event' => 'match.result_reported',
    ])->assertOk();
}

it('dispatches MatchFinalized with SubmittedByParticipants source on participant-driven completion', function () {
    $matchLbId = (string) Str::ulid();
    $competition = Competition::factory()->running()->syncedToLanBrackets()->create();
    $user = User::factory()->create();
    MatchResultProof::create([
        'competition_id' => $competition->id,
        'lanbrackets_match_id' => $matchLbId,
        'submitted_by_user_id' => $user->id,
        'submitted_by_team_id' => null,
        'screenshot_path' => 'proofs/x.png',
        'scores' => [],
    ]);

    Event::fake([MatchFinalized::class]);

    dispatchMatchResultWebhook($competition, $matchLbId, forcedByAdmin: false);

    Event::assertDispatched(
        MatchFinalized::class,
        fn (MatchFinalized $e) => $e->lanbracketsMatchId === $matchLbId
            && $e->source === MatchFinalizationSource::SubmittedByParticipants
            && $e->competition->is($competition),
    );
});

it('dispatches MatchFinalized with ForcedByAdmin source when the webhook flag is set', function () {
    $matchLbId = (string) Str::ulid();
    $competition = Competition::factory()->running()->syncedToLanBrackets()->create();

    Event::fake([MatchFinalized::class]);

    dispatchMatchResultWebhook($competition, $matchLbId, forcedByAdmin: true);

    Event::assertDispatched(
        MatchFinalized::class,
        fn (MatchFinalized $e) => $e->lanbracketsMatchId === $matchLbId
            && $e->source === MatchFinalizationSource::ForcedByAdmin,
    );
});

it('does not dispatch MatchFinalized twice for re-emits of the same match', function () {
    $matchLbId = (string) Str::ulid();
    $competition = Competition::factory()->running()->syncedToLanBrackets()->create();

    Event::fake([MatchFinalized::class]);

    dispatchMatchResultWebhook($competition, $matchLbId);
    dispatchMatchResultWebhook($competition, $matchLbId);
    dispatchMatchResultWebhook($competition, $matchLbId);

    Event::assertDispatchedTimes(MatchFinalized::class, 1);
});

it('still dispatches the existing MatchCompleted event on the same path (regression)', function () {
    $matchLbId = (string) Str::ulid();
    $competition = Competition::factory()->running()->syncedToLanBrackets()->create();

    Event::fake([MatchCompleted::class]);

    dispatchMatchResultWebhook($competition, $matchLbId);

    Event::assertDispatched(
        MatchCompleted::class,
        fn (MatchCompleted $e) => $e->lanbracketsMatchId === $matchLbId,
    );
});
