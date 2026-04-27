<?php

use App\Domain\Event\Models\Event;
use App\Domain\OrgaTeam\Enums\SubTeamRole;
use App\Domain\OrgaTeam\Models\OrgaSubTeam;
use App\Domain\OrgaTeam\Models\OrgaTeam;
use App\Models\User;

it('returns 404 when the event has no orga-team assigned', function () {
    $event = Event::factory()->create(['orga_team_id' => null]);

    $this->get("/events/{$event->id}/orga-team")->assertNotFound();
});

it('renders the OrgChart for an event with an assigned team', function () {
    $organizer = User::factory()->create(['username' => 'orga']);
    $deputy = User::factory()->create(['username' => 'deputy']);
    $team = OrgaTeam::factory()->create([
        'name' => 'SXLAN Crew',
        'organizer_user_id' => $organizer->id,
    ]);
    $team->deputies()->attach($deputy->id, ['sort_order' => 0]);
    $event = Event::factory()->create(['orga_team_id' => $team->id]);

    $sub = OrgaSubTeam::factory()->create([
        'orga_team_id' => $team->id,
        'name' => 'Tech',
        'leader_user_id' => User::factory()->create(['username' => 'techlead'])->id,
    ]);
    $member = User::factory()->create(['username' => 'member1']);
    $sub->users()->attach($member->id, [
        'role' => SubTeamRole::Member->value,
        'sort_order' => 0,
    ]);

    $this->get("/events/{$event->id}/orga-team")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('orga-teams/Public')
                ->where('orgaTeam.name', 'SXLAN Crew')
                ->where('orgaTeam.organizer.username', 'orga')
                ->where('orgaTeam.deputies.0.username', 'deputy')
                ->where('orgaTeam.sub_teams.0.name', 'Tech')
                ->where('orgaTeam.sub_teams.0.leader.username', 'techlead')
                ->where('orgaTeam.sub_teams.0.members.0.username', 'member1')
        );
});

it('is reachable without authentication', function () {
    $team = OrgaTeam::factory()->create();
    $event = Event::factory()->create(['orga_team_id' => $team->id]);

    $this->get("/events/{$event->id}/orga-team")->assertSuccessful();
});
