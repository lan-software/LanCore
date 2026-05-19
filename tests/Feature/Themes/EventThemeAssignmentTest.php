<?php

use App\Domain\Event\Models\Event;
use App\Domain\Theme\Models\Theme;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Str;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
});

it('assigns a theme to an event', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event = Event::factory()->create();
    $theme = Theme::factory()->withPalette()->create();

    $this->actingAs($admin)
        ->patch("/backstage/events/{$event->id}/theme", ['theme_id' => $theme->id])
        ->assertRedirect();

    expect($event->fresh())->theme_id->toBe($theme->id);
});

it('clears a theme assignment when null is posted', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $theme = Theme::factory()->withPalette()->create();
    $event = Event::factory()->create(['theme_id' => $theme->id]);

    $this->actingAs($admin)
        ->patch("/backstage/events/{$event->id}/theme", ['theme_id' => null])
        ->assertRedirect();

    expect($event->fresh())->theme_id->toBeNull();
});

it('rejects assignment of a non-existent theme', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event = Event::factory()->create();

    $this->actingAs($admin)
        ->patch("/backstage/events/{$event->id}/theme", ['theme_id' => (string) Str::ulid()])
        ->assertSessionHasErrors(['theme_id']);
});

it('nulls the events theme_id when the theme is deleted (FK nullOnDelete)', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $theme = Theme::factory()->withPalette()->create();
    $event = Event::factory()->create(['theme_id' => $theme->id]);

    $this->actingAs($admin)
        ->delete("/backstage/themes/{$theme->id}")
        ->assertRedirect('/backstage/themes');

    expect($event->fresh())->theme_id->toBeNull();
});

it('captures theme assignment changes in the event audit trail', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event = Event::factory()->create();
    $theme = Theme::factory()->withPalette()->create();

    $initialAuditCount = $event->audits()->count();

    $this->actingAs($admin)
        ->patch("/backstage/events/{$event->id}/theme", ['theme_id' => $theme->id])
        ->assertRedirect();

    expect($event->fresh()->audits()->count())->toBeGreaterThan($initialAuditCount);
});

it('forbids a regular user from assigning a theme to an event', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $event = Event::factory()->create();
    $theme = Theme::factory()->withPalette()->create();

    $this->actingAs($user)
        ->patch("/backstage/events/{$event->id}/theme", ['theme_id' => $theme->id])
        ->assertForbidden();
});
