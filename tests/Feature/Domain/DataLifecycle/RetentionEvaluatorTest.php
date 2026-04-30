<?php

use App\Domain\DataLifecycle\RetentionEvaluators\AccountingEvaluator;
use App\Domain\Shop\Models\Order;
use App\Models\User;
use Database\Seeders\RetentionPolicySeeder;

beforeEach(function () {
    $this->seed(RetentionPolicySeeder::class);
});

it('does not impose accounting hold on a user without orders or stripe linkage', function () {
    $user = User::factory()->create(['stripe_id' => null]);

    $verdict = app(AccountingEvaluator::class)->evaluate($user);

    expect($verdict->holds)->toBeFalse();
});

it('imposes a 10-year accounting hold when the user has any order', function () {
    $user = User::factory()->create();
    Order::factory()->create(['user_id' => $user->id, 'created_at' => now()]);

    $verdict = app(AccountingEvaluator::class)->evaluate($user);

    expect($verdict->holds)->toBeTrue();
    expect($verdict->until)->not->toBeNull();
    expect($verdict->until->isAfter(now()->addYears(9)))->toBeTrue();
});

it('imposes accounting hold even on users with only a stripe linkage', function () {
    $user = User::factory()->create(['stripe_id' => 'cus_test_123']);

    $verdict = app(AccountingEvaluator::class)->evaluate($user);

    expect($verdict->holds)->toBeTrue();
});
