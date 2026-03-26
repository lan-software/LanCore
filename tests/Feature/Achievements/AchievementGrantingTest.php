<?php

use App\Domain\Achievements\Actions\GrantAchievement;
use App\Domain\Achievements\Enums\GrantableEvent;
use App\Domain\Achievements\Listeners\ProcessAchievements;
use App\Domain\Achievements\Models\Achievement;
use App\Domain\Achievements\Models\AchievementEvent;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use App\Notifications\AchievementEarnedNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
});

it('grants an achievement to a user', function () {
    Notification::fake();

    $user = User::factory()->create();
    $achievement = Achievement::factory()->create();

    $grantAction = app(GrantAchievement::class);
    $result = $grantAction->execute($user, $achievement);

    expect($result)->toBeTrue();
    expect($user->achievements)->toHaveCount(1);
    expect($user->achievements->first()->id)->toBe($achievement->id);

    Notification::assertSentTo($user, AchievementEarnedNotification::class);
});

it('does not grant the same achievement twice', function () {
    Notification::fake();

    $user = User::factory()->create();
    $achievement = Achievement::factory()->create();

    $grantAction = app(GrantAchievement::class);
    $grantAction->execute($user, $achievement);
    $result = $grantAction->execute($user, $achievement);

    expect($result)->toBeFalse();
    expect($user->achievements)->toHaveCount(1);

    Notification::assertSentToTimes($user, AchievementEarnedNotification::class, 1);
});

it('processes achievements when an event fires', function () {
    Notification::fake();

    $achievement = Achievement::factory()->create();
    AchievementEvent::factory()->create([
        'achievement_id' => $achievement->id,
        'event_class' => GrantableEvent::UserRegistered->value,
    ]);

    $user = User::factory()->create();
    $event = new Registered($user);

    $listener = app(ProcessAchievements::class);
    $listener->handle($event);

    $user->refresh();
    expect($user->achievements)->toHaveCount(1);
    expect($user->achievements->first()->id)->toBe($achievement->id);

    Notification::assertSentTo($user, AchievementEarnedNotification::class);
});

it('does not grant inactive achievements', function () {
    Notification::fake();

    $achievement = Achievement::factory()->inactive()->create();
    AchievementEvent::factory()->create([
        'achievement_id' => $achievement->id,
        'event_class' => GrantableEvent::UserRegistered->value,
    ]);

    $user = User::factory()->create();
    $event = new Registered($user);

    $listener = app(ProcessAchievements::class);
    $listener->handle($event);

    $user->refresh();
    expect($user->achievements)->toHaveCount(0);

    Notification::assertNothingSent();
});

it('only grants achievements linked to the fired event', function () {
    Notification::fake();

    $registeredAchievement = Achievement::factory()->create(['name' => 'Welcome']);
    AchievementEvent::factory()->create([
        'achievement_id' => $registeredAchievement->id,
        'event_class' => GrantableEvent::UserRegistered->value,
    ]);

    $otherAchievement = Achievement::factory()->create(['name' => 'Publisher']);
    AchievementEvent::factory()->create([
        'achievement_id' => $otherAchievement->id,
        'event_class' => GrantableEvent::NewsArticlePublished->value,
    ]);

    $user = User::factory()->create();
    $event = new Registered($user);

    $listener = app(ProcessAchievements::class);
    $listener->handle($event);

    $user->refresh();
    expect($user->achievements)->toHaveCount(1);
    expect($user->achievements->first()->name)->toBe('Welcome');
});
