<?php

use App\Domain\DataLifecycle\Actions\AnonymizeUser;
use App\Domain\DataLifecycle\Actions\ForceDeleteUserData;
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

it('creates an admin-induced deletion request with the admin recorded as initiator', function () {
    $admin = User::factory()->withRole(RoleName::Superadmin)->create();
    $subject = User::factory()->create();

    $result = app(RequestUserDeletion::class)->execute(
        subject: $subject,
        initiator: DeletionInitiator::Admin,
        reason: 'Banned for ToS violation',
        requestedByAdmin: $admin,
    );

    $request = DeletionRequest::find($result['request']->id);
    expect($request->initiator)->toBe(DeletionInitiator::Admin);
    expect($request->requested_by_admin_id)->toBe($admin->id);
    expect($request->reason)->toBe('Banned for ToS violation');
});

it('admin can anonymize-now and immediately scrub PII without waiting for grace', function () {
    $admin = User::factory()->withRole(RoleName::Superadmin)->create();
    $subject = User::factory()->withCompleteProfile()->create();

    $result = app(RequestUserDeletion::class)->execute(
        subject: $subject,
        initiator: DeletionInitiator::Admin,
        reason: 'urgent',
        requestedByAdmin: $admin,
    );

    $request = $result['request'];
    $request->update([
        'status' => DeletionRequestStatus::PendingGrace,
        'email_confirmed_at' => now(),
        'scheduled_for' => now()->addDays(30),
    ]);

    app(AnonymizeUser::class)->execute($request->refresh());

    expect($subject->refresh()->isAnonymized())->toBeTrue();
});

it('force-delete removes the user row entirely and creates an audited request record', function () {
    $admin = User::factory()->withRole(RoleName::Superadmin)->create();
    $subject = User::factory()->create();
    $subjectId = $subject->id;

    app(ForceDeleteUserData::class)->execute(
        subject: $subject,
        admin: $admin,
        reason: 'Court order #2026-XYZ requires immediate purge.',
    );

    expect(User::withTrashed()->find($subjectId))->toBeNull();
    $req = DeletionRequest::query()->where('user_id', $subjectId)->first();
    expect($req->status)->toBe(DeletionRequestStatus::ForceDeleted);
    expect($req->reason)->toContain('Court order');
});
