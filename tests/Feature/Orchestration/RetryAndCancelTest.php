<?php

use App\Domain\Orchestration\Enums\OrchestrationJobStatus;
use App\Domain\Orchestration\Jobs\ProcessMatchOrchestration;
use App\Domain\Orchestration\Models\GameServer;
use App\Domain\Orchestration\Models\OrchestrationJob;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('allows admins to retry a failed job', function () {
    Queue::fake();

    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $job = OrchestrationJob::factory()->failed()->create();

    $this->actingAs($admin)
        ->post("/orchestration-jobs/{$job->id}/retry")
        ->assertRedirect();

    expect($job->fresh()->status)->toBe(OrchestrationJobStatus::Pending);
    expect($job->fresh()->error_message)->toBeNull();

    Queue::assertPushed(ProcessMatchOrchestration::class);
});

it('prevents retrying non-failed jobs', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $job = OrchestrationJob::factory()->active()->create();

    $this->actingAs($admin)
        ->post("/orchestration-jobs/{$job->id}/retry")
        ->assertSessionHasErrors('status');
});

it('allows admins to cancel a pending job', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $job = OrchestrationJob::factory()->pending()->create();

    $this->actingAs($admin)
        ->post("/orchestration-jobs/{$job->id}/cancel")
        ->assertRedirect();

    expect($job->fresh()->status)->toBe(OrchestrationJobStatus::Cancelled);
});

it('prevents cancelling active jobs', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $job = OrchestrationJob::factory()->active()->create();

    $this->actingAs($admin)
        ->post("/orchestration-jobs/{$job->id}/cancel")
        ->assertSessionHasErrors('status');
});

it('allows admins to force-release a game server', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $server = GameServer::factory()->inUse()->create();
    $job = OrchestrationJob::factory()->active()->create(['game_server_id' => $server->id]);

    $this->actingAs($admin)
        ->post("/game-servers/{$server->id}/force-release")
        ->assertRedirect();

    expect($server->fresh()->status->value)->toBe('available');
    expect($job->fresh()->status)->toBe(OrchestrationJobStatus::Cancelled);
});
