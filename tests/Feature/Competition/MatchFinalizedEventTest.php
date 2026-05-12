<?php

use App\Domain\Competition\Enums\MatchFinalizationSource;
use App\Domain\Competition\Events\MatchCompleted;
use App\Domain\Competition\Events\MatchFinalized;
use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\MatchResultProof;
use App\Models\User;
use Illuminate\Support\Facades\Event;

function dispatchMatchResultWebhook(Competition $competition, int $matchId, bool $forcedByAdmin = false): void
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
    $competition = Competition::factory()->running()->syncedToLanBrackets()->create();
    $user = User::factory()->create();
    MatchResultProof::create([
        'competition_id' => $competition->id,
        'lanbrackets_match_id' => 9001,
        'submitted_by_user_id' => $user->id,
        'submitted_by_team_id' => null,
        'screenshot_path' => 'proofs/x.png',
        'scores' => [],
    ]);

    Event::fake([MatchFinalized::class]);

    dispatchMatchResultWebhook($competition, 9001, forcedByAdmin: false);

    Event::assertDispatched(
        MatchFinalized::class,
        fn (MatchFinalized $e) => $e->lanbracketsMatchId === 9001
            && $e->source === MatchFinalizationSource::SubmittedByParticipants
            && $e->competition->is($competition),
    );
});

it('dispatches MatchFinalized with ForcedByAdmin source when the webhook flag is set', function () {
    $competition = Competition::factory()->running()->syncedToLanBrackets()->create();

    Event::fake([MatchFinalized::class]);

    dispatchMatchResultWebhook($competition, 9002, forcedByAdmin: true);

    Event::assertDispatched(
        MatchFinalized::class,
        fn (MatchFinalized $e) => $e->lanbracketsMatchId === 9002
            && $e->source === MatchFinalizationSource::ForcedByAdmin,
    );
});

it('does not dispatch MatchFinalized twice for re-emits of the same match', function () {
    $competition = Competition::factory()->running()->syncedToLanBrackets()->create();

    Event::fake([MatchFinalized::class]);

    dispatchMatchResultWebhook($competition, 9003);
    dispatchMatchResultWebhook($competition, 9003);
    dispatchMatchResultWebhook($competition, 9003);

    Event::assertDispatchedTimes(MatchFinalized::class, 1);
});

it('still dispatches the existing MatchCompleted event on the same path (regression)', function () {
    $competition = Competition::factory()->running()->syncedToLanBrackets()->create();

    Event::fake([MatchCompleted::class]);

    dispatchMatchResultWebhook($competition, 9004);

    Event::assertDispatched(
        MatchCompleted::class,
        fn (MatchCompleted $e) => $e->lanbracketsMatchId === 9004,
    );
});
