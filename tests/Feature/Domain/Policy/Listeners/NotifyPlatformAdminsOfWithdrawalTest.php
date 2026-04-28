<?php

use App\Domain\Policy\Enums\Permission as PolicyPermission;
use App\Domain\Policy\Events\ConsentWithdrawn;
use App\Domain\Policy\Listeners\NotifyPlatformAdminsOfWithdrawal;
use App\Domain\Policy\Models\PolicyAcceptance;
use App\Domain\Policy\Notifications\UserWithdrewConsentNotification;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    Role::query()->updateOrCreate(['name' => RoleName::Admin->value]);
    Role::query()->updateOrCreate(['name' => RoleName::User->value]);
});

it('notifies all users holding ManagePolicies but excludes the withdrawing user', function (): void {
    Notification::fake();

    $admins = User::factory()->count(2)->withRole(RoleName::Admin)->create();
    $regularUser = User::factory()->withRole(RoleName::User)->create();
    $withdrawer = User::factory()->withRole(RoleName::Admin)->create();

    $acceptance = PolicyAcceptance::factory()
        ->withdrawn('Privacy concerns')
        ->create(['user_id' => $withdrawer->id]);

    foreach ($admins as $admin) {
        expect($admin->hasPermission(PolicyPermission::ManagePolicies))->toBeTrue();
    }

    app(NotifyPlatformAdminsOfWithdrawal::class)->handle(new ConsentWithdrawn($acceptance));

    foreach ($admins as $admin) {
        Notification::assertSentTo($admin, UserWithdrewConsentNotification::class);
    }

    Notification::assertNotSentTo($regularUser, UserWithdrewConsentNotification::class);
    Notification::assertNotSentTo($withdrawer, UserWithdrewConsentNotification::class);
});
