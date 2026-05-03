<?php

use App\Domain\DataLifecycle\Actions\RequestUserDeletion;
use App\Domain\DataLifecycle\Enums\DeletionInitiator;
use App\Domain\DataLifecycle\Enums\DeletionRequestStatus;
use App\Domain\DataLifecycle\Models\DeletionRequest;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RetentionPolicySeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seed(RetentionPolicySeeder::class);
    Mail::fake();

    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('opens, confirms and anonymizes a user in one admin call', function () {
    $admin = User::factory()->withRole(RoleName::Superadmin)->create();
    $subject = User::factory()->create();

    $this->actingAs($admin)
        ->post("/admin/data-lifecycle/users/{$subject->id}/anonymize-immediately", [
            'reason' => 'Operator-initiated test cleanup',
        ])
        ->assertRedirect();

    $request = DeletionRequest::query()
        ->where('user_id', $subject->id)
        ->latest('id')
        ->first();

    expect($request)->not->toBeNull()
        ->and($request->status)->toBe(DeletionRequestStatus::Anonymized)
        ->and($request->anonymized_at)->not->toBeNull()
        ->and($request->initiator)->toBe(DeletionInitiator::Admin)
        ->and($request->requested_by_admin_id)->toBe($admin->id)
        ->and($request->reason)->toBe('Operator-initiated test cleanup');

    expect($subject->fresh()->isAnonymized())->toBeTrue();
});

it('rejects anonymize-immediately when an active request already exists', function () {
    $admin = User::factory()->withRole(RoleName::Superadmin)->create();
    $subject = User::factory()->create();

    app(RequestUserDeletion::class)->execute(
        subject: $subject,
        initiator: DeletionInitiator::Admin,
        reason: 'Pre-existing request',
        requestedByAdmin: $admin,
    );

    $this->actingAs($admin)
        ->post("/admin/data-lifecycle/users/{$subject->id}/anonymize-immediately", [
            'reason' => 'Trying to short-circuit',
        ])
        ->assertStatus(500);
});

it('forbids anonymize-immediately for users without RequestUserDeletion', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $subject = User::factory()->create();

    $this->actingAs($user)
        ->post("/admin/data-lifecycle/users/{$subject->id}/anonymize-immediately", [
            'reason' => 'Should be blocked',
        ])
        ->assertForbidden();
});

it('validates that a reason is provided', function () {
    $admin = User::factory()->withRole(RoleName::Superadmin)->create();
    $subject = User::factory()->create();

    $this->actingAs($admin)
        ->post("/admin/data-lifecycle/users/{$subject->id}/anonymize-immediately", [
            'reason' => '',
        ])
        ->assertSessionHasErrors('reason');
});

it('lets anonymize-now fire from pending_email_confirm via the existing endpoint', function () {
    $admin = User::factory()->withRole(RoleName::Superadmin)->create();
    $subject = User::factory()->create();

    $opened = app(RequestUserDeletion::class)->execute(
        subject: $subject,
        initiator: DeletionInitiator::Admin,
        reason: 'For test',
        requestedByAdmin: $admin,
    );

    $this->actingAs($admin)
        ->post(
            "/admin/data-lifecycle/deletion-requests/{$opened['request']->id}/anonymize-now",
        )
        ->assertRedirect();

    $request = DeletionRequest::find($opened['request']->id);
    expect($request->status)->toBe(DeletionRequestStatus::Anonymized);
});
