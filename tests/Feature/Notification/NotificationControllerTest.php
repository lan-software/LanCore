<?php

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('shows the notifications index page to authenticated users', function () {
    $this->actingAs($this->user)
        ->get('/notifications')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('notifications/Index')
            ->has('notifications'));
});

it('redirects guests from the notifications page', function () {
    $this->get('/notifications')
        ->assertRedirect('/login');
});

it('shows paginated notifications belonging to the authenticated user', function () {
    DatabaseNotification::create([
        'id' => (string) Str::uuid(),
        'type' => 'App\Notifications\UserAttributesUpdatedNotification',
        'notifiable_type' => User::class,
        'notifiable_id' => $this->user->id,
        'data' => json_encode(['changed_attributes' => ['name']]),
    ]);

    $otherUser = User::factory()->create();
    DatabaseNotification::create([
        'id' => (string) Str::uuid(),
        'type' => 'App\Notifications\UserAttributesUpdatedNotification',
        'notifiable_type' => User::class,
        'notifiable_id' => $otherUser->id,
        'data' => json_encode(['changed_attributes' => ['email']]),
    ]);

    $this->actingAs($this->user)
        ->get('/notifications')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('notifications/Index')
            ->where('notifications.total', 1));
});

it('allows marking a single notification as read', function () {
    $notificationId = (string) Str::uuid();
    DatabaseNotification::create([
        'id' => $notificationId,
        'type' => 'App\Notifications\UserAttributesUpdatedNotification',
        'notifiable_type' => User::class,
        'notifiable_id' => $this->user->id,
        'data' => json_encode(['changed_attributes' => ['name']]),
        'read_at' => null,
    ]);

    $this->actingAs($this->user)
        ->patch("/notifications/{$notificationId}/read")
        ->assertRedirect();

    expect(DatabaseNotification::find($notificationId)->read_at)->not->toBeNull();
});

it('prevents users from marking other users\' notifications as read', function () {
    $otherUser = User::factory()->create();
    $notificationId = (string) Str::uuid();
    DatabaseNotification::create([
        'id' => $notificationId,
        'type' => 'App\Notifications\UserAttributesUpdatedNotification',
        'notifiable_type' => User::class,
        'notifiable_id' => $otherUser->id,
        'data' => json_encode(['changed_attributes' => ['name']]),
    ]);

    $this->actingAs($this->user)
        ->patch("/notifications/{$notificationId}/read")
        ->assertNotFound();
});

it('allows marking all notifications as read', function () {
    foreach (range(1, 3) as $_) {
        DatabaseNotification::create([
            'id' => (string) Str::uuid(),
            'type' => 'App\Notifications\UserAttributesUpdatedNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $this->user->id,
            'data' => json_encode(['changed_attributes' => ['name']]),
            'read_at' => null,
        ]);
    }

    $this->actingAs($this->user)
        ->patch('/notifications/read-all')
        ->assertRedirect();

    expect($this->user->unreadNotifications()->count())->toBe(0);
});

it('allows deleting (archiving) a notification', function () {
    $notificationId = (string) Str::uuid();
    DatabaseNotification::create([
        'id' => $notificationId,
        'type' => 'App\Notifications\UserAttributesUpdatedNotification',
        'notifiable_type' => User::class,
        'notifiable_id' => $this->user->id,
        'data' => json_encode(['changed_attributes' => ['name']]),
    ]);

    $this->actingAs($this->user)
        ->delete("/notifications/{$notificationId}")
        ->assertRedirect();

    expect(DatabaseNotification::find($notificationId))->toBeNull();
});

it('prevents users from deleting other users\' notifications', function () {
    $otherUser = User::factory()->create();
    $notificationId = (string) Str::uuid();
    DatabaseNotification::create([
        'id' => $notificationId,
        'type' => 'App\Notifications\UserAttributesUpdatedNotification',
        'notifiable_type' => User::class,
        'notifiable_id' => $otherUser->id,
        'data' => json_encode(['changed_attributes' => ['name']]),
    ]);

    $this->actingAs($this->user)
        ->delete("/notifications/{$notificationId}")
        ->assertNotFound();
});

it('shares unread notifications count via inertia props', function () {
    DatabaseNotification::create([
        'id' => (string) Str::uuid(),
        'type' => 'App\Notifications\UserAttributesUpdatedNotification',
        'notifiable_type' => User::class,
        'notifiable_id' => $this->user->id,
        'data' => json_encode(['changed_attributes' => ['name']]),
        'read_at' => null,
    ]);

    $this->actingAs($this->user)
        ->get('/notifications')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('unreadNotificationsCount', 1));
});
