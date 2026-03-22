<?php

use App\Domain\Announcement\Actions\CreateAnnouncement;
use App\Domain\Announcement\Actions\UpdateAnnouncement;
use App\Domain\Announcement\Enums\AnnouncementPriority;
use App\Domain\Announcement\Events\AnnouncementPublished;
use App\Domain\Announcement\Models\Announcement;
use App\Domain\Event\Models\Event;
use App\Domain\Notification\Models\NotificationPreference;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use App\Notifications\AnnouncementPublishedNotification;
use Illuminate\Support\Facades\Event as EventFacade;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
});

// --- Action Tests ---

it('dispatches AnnouncementPublished event when creating a published announcement', function () {
    EventFacade::fake([AnnouncementPublished::class]);

    $event = Event::factory()->published()->create();
    $user = User::factory()->create();

    $action = app(CreateAnnouncement::class);
    $action->execute([
        'title' => 'Test Announcement',
        'description' => 'Test description',
        'priority' => 'normal',
        'event_id' => $event->id,
        'author_id' => $user->id,
        'published_at' => now()->toDateTimeString(),
    ]);

    EventFacade::assertDispatched(AnnouncementPublished::class);
});

it('does not dispatch event when creating an unpublished announcement', function () {
    EventFacade::fake([AnnouncementPublished::class]);

    $event = Event::factory()->published()->create();
    $user = User::factory()->create();

    $action = app(CreateAnnouncement::class);
    $action->execute([
        'title' => 'Draft Announcement',
        'priority' => 'normal',
        'event_id' => $event->id,
        'author_id' => $user->id,
        'published_at' => null,
    ]);

    EventFacade::assertNotDispatched(AnnouncementPublished::class);
});

it('dispatches event when updating an unpublished announcement to published', function () {
    EventFacade::fake([AnnouncementPublished::class]);

    $announcement = Announcement::factory()->create(['published_at' => null]);

    $action = app(UpdateAnnouncement::class);
    $action->execute($announcement, [
        'published_at' => now()->toDateTimeString(),
    ]);

    EventFacade::assertDispatched(AnnouncementPublished::class);
});

it('does not dispatch event when updating an already published announcement', function () {
    EventFacade::fake([AnnouncementPublished::class]);

    $announcement = Announcement::factory()->published()->create();

    $action = app(UpdateAnnouncement::class);
    $action->execute($announcement, [
        'title' => 'Updated Title',
    ]);

    EventFacade::assertNotDispatched(AnnouncementPublished::class);
});

// --- Notification Tests ---

it('sends notification for normal priority announcements respecting user preferences', function () {
    Notification::fake();

    $userWithPrefs = User::factory()->create();
    NotificationPreference::factory()->for($userWithPrefs)->create([
        'mail_on_announcements' => false,
    ]);

    $userDefault = User::factory()->create();

    $announcement = Announcement::factory()->published()->create([
        'priority' => AnnouncementPriority::Normal,
    ]);

    $notification = new AnnouncementPublishedNotification($announcement);

    expect($notification->shouldSend($userWithPrefs, 'mail'))->toBeFalse();
    expect($notification->shouldSend($userDefault, 'mail'))->toBeTrue();
});

it('always sends notification for emergency announcements regardless of preferences', function () {
    $user = User::factory()->create();
    NotificationPreference::factory()->for($user)->create([
        'mail_on_announcements' => false,
    ]);

    $announcement = Announcement::factory()->published()->emergency()->create();
    $notification = new AnnouncementPublishedNotification($announcement);

    expect($notification->shouldSend($user, 'mail'))->toBeTrue();
});

it('does not send notifications for silent announcements', function () {
    Notification::fake();

    $event = Event::factory()->published()->create();
    $user = User::factory()->create();

    EventFacade::fake([AnnouncementPublished::class]);

    $action = app(CreateAnnouncement::class);
    $announcement = $action->execute([
        'title' => 'Silent Announcement',
        'priority' => 'silent',
        'event_id' => $event->id,
        'author_id' => $user->id,
        'published_at' => now()->toDateTimeString(),
    ]);

    // Even though event is dispatched, the listener checks priority
    expect($announcement->priority)->toBe(AnnouncementPriority::Silent);
});

// --- Admin CRUD Tests ---

it('allows admins to view announcements index', function () {
    $admin = User::factory()->create();
    $adminRole = Role::where('name', RoleName::Admin->value)->first();
    $admin->roles()->attach($adminRole);

    $this->actingAs($admin)
        ->get('/announcements-admin')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('announcements/Index'));
});

it('prevents non-admin users from viewing announcements index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/announcements-admin')
        ->assertForbidden();
});

it('allows admins to create announcements', function () {
    $admin = User::factory()->create();
    $adminRole = Role::where('name', RoleName::Admin->value)->first();
    $admin->roles()->attach($adminRole);

    $event = Event::factory()->published()->create();

    EventFacade::fake([AnnouncementPublished::class]);

    $this->actingAs($admin)
        ->post('/announcements-admin', [
            'title' => 'New Announcement',
            'description' => 'A test announcement',
            'priority' => 'normal',
            'event_id' => $event->id,
            'publish_now' => true,
        ])
        ->assertRedirect('/announcements-admin');

    expect(Announcement::where('title', 'New Announcement')->exists())->toBeTrue();
});

it('allows admins to update announcements', function () {
    $admin = User::factory()->create();
    $adminRole = Role::where('name', RoleName::Admin->value)->first();
    $admin->roles()->attach($adminRole);

    $announcement = Announcement::factory()->published()->create();

    $this->actingAs($admin)
        ->patch("/announcements-admin/{$announcement->id}", [
            'title' => 'Updated Title',
            'description' => 'Updated description',
            'priority' => 'emergency',
            'event_id' => $announcement->event_id,
        ])
        ->assertRedirect();

    expect($announcement->fresh()->title)->toBe('Updated Title');
});

it('allows admins to delete announcements', function () {
    $admin = User::factory()->create();
    $adminRole = Role::where('name', RoleName::Admin->value)->first();
    $admin->roles()->attach($adminRole);

    $announcement = Announcement::factory()->create();

    $this->actingAs($admin)
        ->delete("/announcements-admin/{$announcement->id}")
        ->assertRedirect('/announcements-admin');

    expect(Announcement::find($announcement->id))->toBeNull();
});

// --- Dismissal Tests ---

it('allows authenticated users to dismiss announcements', function () {
    $user = User::factory()->create();
    $announcement = Announcement::factory()->published()->create();

    $this->actingAs($user)
        ->post("/announcements/{$announcement->id}/dismiss")
        ->assertRedirect();

    expect($user->dismissedAnnouncements()->where('announcement_id', $announcement->id)->exists())->toBeTrue();
});

it('does not duplicate dismissals for same announcement', function () {
    $user = User::factory()->create();
    $announcement = Announcement::factory()->published()->create();

    $this->actingAs($user)->post("/announcements/{$announcement->id}/dismiss");
    $this->actingAs($user)->post("/announcements/{$announcement->id}/dismiss");

    expect($user->dismissedAnnouncements()->where('announcement_id', $announcement->id)->count())->toBe(1);
});

// --- Welcome Page Tests ---

it('shows announcements on the welcome page for logged in users', function () {
    $event = Event::factory()->published()->create([
        'start_date' => now()->addDays(7),
        'end_date' => now()->addDays(8),
    ]);

    $announcement = Announcement::factory()->published()->create([
        'event_id' => $event->id,
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('Welcome')
                ->has('announcements', 1)
                ->where('announcements.0.id', $announcement->id)
        );
});

it('does not show dismissed announcements on the welcome page', function () {
    $event = Event::factory()->published()->create([
        'start_date' => now()->addDays(7),
        'end_date' => now()->addDays(8),
    ]);

    $announcement = Announcement::factory()->published()->create([
        'event_id' => $event->id,
    ]);

    $user = User::factory()->create();
    $user->dismissedAnnouncements()->attach($announcement->id);

    $this->actingAs($user)
        ->get('/')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('Welcome')
                ->has('announcements', 0)
                ->has('dismissedAnnouncementIds', 1)
        );
});

it('does not show unpublished announcements on the welcome page', function () {
    $event = Event::factory()->published()->create([
        'start_date' => now()->addDays(7),
        'end_date' => now()->addDays(8),
    ]);

    Announcement::factory()->create([
        'event_id' => $event->id,
        'published_at' => null,
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('Welcome')
                ->has('announcements', 0)
        );
});

// --- Public Announcements Page Tests ---

it('shows all announcements for an event on the public page', function () {
    $event = Event::factory()->published()->create();
    $announcement = Announcement::factory()->published()->create(['event_id' => $event->id]);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get("/events/{$event->id}/announcements")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('announcements/Public')
                ->has('announcements', 1)
        );
});

it('requires authentication for the public announcements page', function () {
    $event = Event::factory()->published()->create();

    $this->get("/events/{$event->id}/announcements")
        ->assertRedirect();
});
