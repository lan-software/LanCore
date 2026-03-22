<?php

use App\Domain\Notification\Models\NotificationPreference;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
});

it('allows authenticated users to view notification settings', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/settings/notifications')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('settings/Notifications')
            ->has('preferences'));
});

it('creates default preferences when visiting settings for the first time', function () {
    $user = User::factory()->create();

    expect(NotificationPreference::where('user_id', $user->id)->exists())->toBeFalse();

    $this->actingAs($user)
        ->get('/settings/notifications')
        ->assertSuccessful();

    $preference = NotificationPreference::where('user_id', $user->id)->first();
    expect($preference)->not->toBeNull();
    expect($preference->mail_on_news)->toBeTrue();
    expect($preference->mail_on_events)->toBeTrue();
    expect($preference->mail_on_news_comments)->toBeTrue();
    expect($preference->mail_on_program_time_slots)->toBeTrue();
    expect($preference->push_on_news)->toBeFalse();
    expect($preference->push_on_events)->toBeFalse();
    expect($preference->push_on_news_comments)->toBeFalse();
    expect($preference->push_on_program_time_slots)->toBeFalse();
});

it('allows users to update their notification preferences', function () {
    $user = User::factory()->create();
    NotificationPreference::factory()->for($user)->create();

    $this->actingAs($user)
        ->patch('/settings/notifications', [
            'mail_on_news' => false,
            'mail_on_events' => true,
            'mail_on_news_comments' => false,
            'mail_on_program_time_slots' => true,
            'push_on_news' => false,
            'push_on_events' => false,
            'push_on_news_comments' => false,
            'push_on_program_time_slots' => false,
        ])
        ->assertRedirect();

    $preference = NotificationPreference::where('user_id', $user->id)->first();
    expect($preference->mail_on_news)->toBeFalse();
    expect($preference->mail_on_events)->toBeTrue();
    expect($preference->mail_on_news_comments)->toBeFalse();
    expect($preference->mail_on_program_time_slots)->toBeTrue();
});

it('validates notification preference fields are required', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch('/settings/notifications', [])
        ->assertInvalid([
            'mail_on_news', 'mail_on_events', 'mail_on_news_comments', 'mail_on_program_time_slots',
            'push_on_news', 'push_on_events', 'push_on_news_comments', 'push_on_program_time_slots',
        ]);
});

it('requires authentication to access notification settings', function () {
    $this->get('/settings/notifications')
        ->assertRedirect('/login');
});
