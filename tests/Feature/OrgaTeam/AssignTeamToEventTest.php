<?php

use App\Domain\Event\Models\Event;
use App\Domain\OrgaTeam\Models\OrgaTeam;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
});

it('admins can assign an orga-team to an event', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event = Event::factory()->create();
    $team = OrgaTeam::factory()->create();

    $this->actingAs($admin)
        ->patch("/events/{$event->id}/orga-team", ['orga_team_id' => $team->id])
        ->assertRedirect();

    expect($event->fresh()->orga_team_id)->toBe($team->id);
});

it('admins can unassign an orga-team from an event', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $team = OrgaTeam::factory()->create();
    $event = Event::factory()->create(['orga_team_id' => $team->id]);

    $this->actingAs($admin)
        ->patch("/events/{$event->id}/orga-team", ['orga_team_id' => null])
        ->assertRedirect();

    expect($event->fresh()->orga_team_id)->toBeNull();
});

it('forbids non-admins from assigning teams to events', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $event = Event::factory()->create();
    $team = OrgaTeam::factory()->create();

    $this->actingAs($user)
        ->patch("/events/{$event->id}/orga-team", ['orga_team_id' => $team->id])
        ->assertForbidden();

    expect($event->fresh()->orga_team_id)->toBeNull();
});

it('rejects assignment of non-existent orga-team id', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event = Event::factory()->create();

    $this->actingAs($admin)
        ->patch("/events/{$event->id}/orga-team", ['orga_team_id' => 999999])
        ->assertSessionHasErrors('orga_team_id');
});

it('deleting the orga-team nullifies the events orga_team_id', function () {
    $team = OrgaTeam::factory()->create();
    $event = Event::factory()->create(['orga_team_id' => $team->id]);

    $team->delete();

    expect($event->fresh()->orga_team_id)->toBeNull();
});
