<?php

use App\Domain\Achievements\Actions\GrantAchievement;
use App\Domain\Achievements\Enums\GrantableEvent;
use App\Domain\Achievements\Listeners\ProcessAchievements;
use App\Domain\Achievements\Models\Achievement;
use App\Domain\Achievements\Models\AchievementEvent;
use App\Domain\Achievements\Notifications\AchievementEarnedNotification;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
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

it('sends a notification with achievement details', function () {
    Notification::fake();

    $user = User::factory()->create();
    $achievement = Achievement::factory()->create([
        'name' => 'First Steps',
        'description' => 'Welcome to the platform!',
        'notification_text' => 'You just earned your first achievement!',
        'color' => '#10b981',
        'icon' => 'star',
    ]);

    $grantAction = app(GrantAchievement::class);
    $grantAction->execute($user, $achievement);

    Notification::assertSentTo($user, AchievementEarnedNotification::class, function ($notification) use ($user, $achievement) {
        $data = $notification->toArray($user);

        expect($data)->toHaveKeys(['achievement_id', 'name', 'description', 'color', 'icon'])
            ->and($data['achievement_id'])->toBe($achievement->id)
            ->and($data['name'])->toBe('First Steps')
            ->and($data['description'])->toBe('You just earned your first achievement!')
            ->and($data['color'])->toBe('#10b981')
            ->and($data['icon'])->toBe('star');

        return true;
    });
});

it('falls back to description when notification_text is null', function () {
    Notification::fake();

    $user = User::factory()->create();
    $achievement = Achievement::factory()->create([
        'name' => 'Explorer',
        'description' => 'You explored the app!',
        'notification_text' => null,
    ]);

    $grantAction = app(GrantAchievement::class);
    $grantAction->execute($user, $achievement);

    Notification::assertSentTo($user, AchievementEarnedNotification::class, function ($notification) use ($user) {
        $data = $notification->toArray($user);

        expect($data['description'])->toBe('You explored the app!');

        return true;
    });
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
