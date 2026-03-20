<?php

use App\Domain\Event\Models\Event;
use App\Domain\Venue\Models\Venue;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('allows admins to view the create event page', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->get('/events/create')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('events/Create')
                ->has('venues')
        );
});

it('allows admins to store a new event', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $venue = Venue::factory()->create();

    $this->actingAs($admin)
        ->post('/events', [
            'name' => 'Summer LAN Party',
            'description' => 'The best LAN event.',
            'start_date' => '2026-07-01 10:00:00',
            'end_date' => '2026-07-03 18:00:00',
            'venue_id' => $venue->id,
        ])
        ->assertRedirect('/events');

    expect(Event::where('name', 'Summer LAN Party')->exists())->toBeTrue();
});

it('validates required fields when storing an event', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/events', [])
        ->assertSessionHasErrors(['name', 'start_date', 'end_date']);
});

it('validates end_date is after start_date', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/events', [
            'name' => 'Bad Dates Event',
            'start_date' => '2026-07-03 18:00:00',
            'end_date' => '2026-07-01 10:00:00',
        ])
        ->assertSessionHasErrors(['end_date']);
});

it('allows admins to view the edit event page', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event = Event::factory()->create();

    $this->actingAs($admin)
        ->get("/events/{$event->id}")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('events/Edit')
                ->has('event')
                ->has('venues')
                ->where('event.id', $event->id)
        );
});

it('allows admins to update an event', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event = Event::factory()->create();

    $this->actingAs($admin)
        ->patch("/events/{$event->id}", [
            'name' => 'Updated Event Name',
            'start_date' => '2026-08-01 09:00:00',
            'end_date' => '2026-08-03 17:00:00',
        ])
        ->assertRedirect();

    expect($event->fresh())
        ->name->toBe('Updated Event Name')
        ->status->value->toBe('draft');
});

it('allows admins to delete an event', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event = Event::factory()->create();

    $this->actingAs($admin)
        ->delete("/events/{$event->id}")
        ->assertRedirect('/events');

    expect(Event::find($event->id))->toBeNull();
});

it('forbids users from creating events', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->post('/events', [
            'name' => 'Test',
            'start_date' => '2026-07-01 10:00:00',
            'end_date' => '2026-07-03 18:00:00',
        ])
        ->assertForbidden();
});

it('stores a banner image file when creating an event', function () {
    Storage::fake();
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/events', [
            'name' => 'Event With Banner',
            'start_date' => '2026-07-01 10:00:00',
            'end_date' => '2026-07-03 18:00:00',
            'banner_image' => UploadedFile::fake()->image('banner.jpg', 1200, 600),
        ])
        ->assertRedirect('/events');

    $event = Event::where('name', 'Event With Banner')->first();
    expect($event->banner_image)->not->toBeNull();
    Storage::assertExists($event->banner_image);
});

it('replaces banner image when updating an event', function () {
    Storage::fake();
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $oldFile = UploadedFile::fake()->image('old.jpg');
    $oldPath = $oldFile->store('events/banners');
    $event = Event::factory()->create(['banner_image' => $oldPath]);

    $this->actingAs($admin)
        ->patch("/events/{$event->id}", [
            'name' => $event->name,
            'start_date' => $event->start_date->format('Y-m-d H:i:s'),
            'end_date' => $event->end_date->format('Y-m-d H:i:s'),
            'banner_image' => UploadedFile::fake()->image('new.jpg', 800, 400),
        ])
        ->assertRedirect();

    $event->refresh();
    Storage::assertExists($event->banner_image);
    Storage::assertMissing($oldPath);
});

it('removes banner image when remove flag is set', function () {
    Storage::fake();
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $file = UploadedFile::fake()->image('banner.jpg');
    $path = $file->store('events/banners');
    $event = Event::factory()->create(['banner_image' => $path]);

    $this->actingAs($admin)
        ->patch("/events/{$event->id}", [
            'name' => $event->name,
            'start_date' => $event->start_date->format('Y-m-d H:i:s'),
            'end_date' => $event->end_date->format('Y-m-d H:i:s'),
            'remove_banner_image' => true,
        ])
        ->assertRedirect();

    $event->refresh();
    expect($event->banner_image)->toBeNull();
    Storage::assertMissing($path);
});

it('deletes banner image from storage when deleting an event', function () {
    Storage::fake();
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $file = UploadedFile::fake()->image('banner.jpg');
    $path = $file->store('events/banners');
    $event = Event::factory()->create(['banner_image' => $path]);

    $this->actingAs($admin)
        ->delete("/events/{$event->id}")
        ->assertRedirect('/events');

    Storage::assertMissing($path);
});

it('rejects non-image files for banner image', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/events', [
            'name' => 'Bad Upload',
            'start_date' => '2026-07-01 10:00:00',
            'end_date' => '2026-07-03 18:00:00',
            'banner_image' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ])
        ->assertSessionHasErrors(['banner_image']);
});
