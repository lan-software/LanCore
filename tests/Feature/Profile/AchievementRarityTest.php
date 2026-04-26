<?php

use App\Domain\Achievements\Actions\GrantAchievement;
use App\Domain\Achievements\Models\Achievement;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * @see docs/mil-std-498/STD.md §4.26 Achievement Rarity Tests
 * @see docs/mil-std-498/SRS.md ACH-F-008
 */
beforeEach(function (): void {
    Cache::forget('users.count');
});

test('earned_user_count starts at zero on a fresh achievement', function () {
    $achievement = Achievement::factory()->create();

    expect($achievement->fresh()->earned_user_count)->toBe(0);
});

test('grant increments earned_user_count', function () {
    $achievement = Achievement::factory()->create();
    $user = User::factory()->create();

    app(GrantAchievement::class)->execute($user, $achievement);

    expect($achievement->fresh()->earned_user_count)->toBe(1);
});

test('repeated grant to the same user does not double-increment', function () {
    $achievement = Achievement::factory()->create();
    $user = User::factory()->create();

    app(GrantAchievement::class)->execute($user, $achievement);
    app(GrantAchievement::class)->execute($user, $achievement);

    expect($achievement->fresh()->earned_user_count)->toBe(1);
});

test('backfill command recomputes counts from pivot data', function () {
    $achievement = Achievement::factory()->create(['earned_user_count' => 0]);
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $userA->achievements()->attach($achievement->id, ['earned_at' => now()]);
    $userB->achievements()->attach($achievement->id, ['earned_at' => now()]);

    $this->artisan('profiles:backfill-achievement-counts')->assertExitCode(0);

    expect($achievement->fresh()->earned_user_count)->toBe(2);
});
