<?php

use App\Domain\Policy\Events\ConsentWithdrawn;
use App\Domain\Policy\Models\Policy;
use App\Domain\Policy\Models\PolicyAcceptance;
use App\Domain\Policy\Models\PolicyVersion;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    Role::query()->updateOrCreate(['name' => RoleName::User->value]);
});

it('records a withdrawal and dispatches ConsentWithdrawn', function (): void {
    Event::fake([ConsentWithdrawn::class]);

    $user = User::factory()->withRole(RoleName::User)->create();
    $policy = Policy::factory()->create(['key' => 'tos']);
    $version = PolicyVersion::factory()->for($policy)->create();
    $acceptance = PolicyAcceptance::factory()->create([
        'user_id' => $user->id,
        'policy_version_id' => $version->id,
    ]);

    $this->actingAs($user)
        ->post('/settings/consent/'.$policy->key.'/withdraw', [
            'reason' => 'Privacy concerns',
        ])
        ->assertRedirect();

    expect($acceptance->fresh()->withdrawn_at)->not->toBeNull();
    Event::assertDispatched(ConsentWithdrawn::class);
});

it('returns to the previous page with an error when no active acceptance exists', function (): void {
    $user = User::factory()->withRole(RoleName::User)->create();
    $policy = Policy::factory()->create(['key' => 'tos']);

    $this->actingAs($user)
        ->from('/settings/privacy')
        ->post('/settings/consent/'.$policy->key.'/withdraw', [])
        ->assertRedirect('/settings/privacy')
        ->assertSessionHasErrors('consent');
});
