<?php

use App\Domain\Policy\Actions\RecordPolicyAcceptance;
use App\Domain\Policy\Enums\PolicyAcceptanceSource;
use App\Domain\Policy\Events\PolicyAccepted;
use App\Domain\Policy\Models\PolicyAcceptance;
use App\Domain\Policy\Models\PolicyVersion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;

it('creates a fresh acceptance row capturing locale, IP, user agent and source', function (): void {
    Event::fake([PolicyAccepted::class]);

    $user = User::factory()->create();
    $version = PolicyVersion::factory()->create(['locale' => 'de']);
    $request = Request::create('/foo', 'POST', server: [
        'REMOTE_ADDR' => '203.0.113.7',
        'HTTP_USER_AGENT' => 'Mozilla/Test',
    ]);

    $acceptance = app(RecordPolicyAcceptance::class)->execute(
        $user,
        $version,
        PolicyAcceptanceSource::Settings,
        $request,
    );

    expect($acceptance->locale)->toBe('de')
        ->and($acceptance->ip_address)->toBe('203.0.113.7')
        ->and($acceptance->user_agent)->toBe('Mozilla/Test')
        ->and($acceptance->source)->toBe(PolicyAcceptanceSource::Settings)
        ->and($acceptance->withdrawn_at)->toBeNull();

    Event::assertDispatched(PolicyAccepted::class, fn ($e) => $e->acceptance->id === $acceptance->id);
});

it('clears withdrawal columns on re-acceptance after a prior withdrawal', function (): void {
    $user = User::factory()->create();
    $version = PolicyVersion::factory()->create();

    $existing = PolicyAcceptance::factory()
        ->withdrawn('user changed mind')
        ->create([
            'user_id' => $user->id,
            'policy_version_id' => $version->id,
        ]);

    $reaccepted = app(RecordPolicyAcceptance::class)->execute(
        $user,
        $version,
        PolicyAcceptanceSource::ReAcceptanceGate,
    );

    expect($reaccepted->id)->toBe($existing->id)
        ->and($reaccepted->withdrawn_at)->toBeNull()
        ->and($reaccepted->withdrawn_reason)->toBeNull()
        ->and($reaccepted->source)->toBe(PolicyAcceptanceSource::ReAcceptanceGate);
});
