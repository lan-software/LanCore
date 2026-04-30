<?php

use App\Domain\DataLifecycle\Actions\AnonymizeUser;
use App\Domain\DataLifecycle\Actions\ConfirmUserDeletion;
use App\Domain\DataLifecycle\Actions\RequestUserDeletion;
use App\Domain\DataLifecycle\Enums\DeletionInitiator;
use App\Domain\DataLifecycle\Services\EmailHasher;
use App\Models\User;
use Database\Seeders\RetentionPolicySeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seed(RetentionPolicySeeder::class);
    Mail::fake();
});

it('locates an anonymized user via email_hash for post-deletion GDPR export', function () {
    $user = User::factory()->create(['email' => 'gdpr-after-delete@example.com']);
    $hash = app(EmailHasher::class)->hash('gdpr-after-delete@example.com');

    expect($user->email_hash)->toBe($hash);

    [$request, $token] = array_values(app(RequestUserDeletion::class)->execute(
        subject: $user,
        initiator: DeletionInitiator::User,
    ));
    $confirmed = app(ConfirmUserDeletion::class)->execute($token);
    app(AnonymizeUser::class)->execute($confirmed);

    $user->refresh();

    expect(User::query()->where('email', 'gdpr-after-delete@example.com')->first())->toBeNull();

    $found = User::withTrashed()->where('email_hash', $hash)->first();
    expect($found)->not->toBeNull();
    expect($found->id)->toBe($user->id);
    expect($found->isAnonymized())->toBeTrue();
});
