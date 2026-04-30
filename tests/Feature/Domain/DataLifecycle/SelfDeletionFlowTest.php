<?php

use App\Domain\DataLifecycle\Actions\AnonymizeUser;
use App\Domain\DataLifecycle\Actions\ConfirmUserDeletion;
use App\Domain\DataLifecycle\Actions\RequestUserDeletion;
use App\Domain\DataLifecycle\Enums\DeletionInitiator;
use App\Domain\DataLifecycle\Enums\DeletionRequestStatus;
use App\Domain\DataLifecycle\Mail\DeletionConfirmationMail;
use App\Domain\DataLifecycle\Mail\DeletionScheduledMail;
use App\Models\User;
use Database\Seeders\RetentionPolicySeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seed(RetentionPolicySeeder::class);
    Mail::fake();
});

it('walks request → email → confirm → grace → anonymize end to end', function () {
    $user = User::factory()->withCompleteProfile()->create([
        'email' => 'happy-path@example.com',
        'name' => 'Happy Path',
    ]);

    [$request, $token] = array_values(app(RequestUserDeletion::class)->execute(
        subject: $user,
        initiator: DeletionInitiator::User,
    ));

    expect($request->status)->toBe(DeletionRequestStatus::PendingEmailConfirm);
    expect($user->refresh()->pending_deletion_at)->not->toBeNull();
    Mail::assertQueued(DeletionConfirmationMail::class);

    $confirmed = app(ConfirmUserDeletion::class)->execute($token);
    expect($confirmed->status)->toBe(DeletionRequestStatus::PendingGrace);
    expect($confirmed->scheduled_for)->not->toBeNull();
    Mail::assertQueued(DeletionScheduledMail::class);

    app(AnonymizeUser::class)->execute($confirmed);

    $user->refresh();
    expect($user->isAnonymized())->toBeTrue();
    expect($user->email)->toBe("deleted-{$user->id}@anonymized.invalid");
    expect($user->name)->toStartWith('Deleted User #');
    expect($user->phone)->toBeNull();
    expect($user->street)->toBeNull();
});

it('blocks duplicate requests when one is already pending', function () {
    $user = User::factory()->create();

    app(RequestUserDeletion::class)->execute(
        subject: $user,
        initiator: DeletionInitiator::User,
    );

    expect(fn () => app(RequestUserDeletion::class)->execute(
        subject: $user,
        initiator: DeletionInitiator::User,
    ))->toThrow(RuntimeException::class);
});
