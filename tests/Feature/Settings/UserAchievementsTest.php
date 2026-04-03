<?php

use App\Domain\Achievements\Models\Achievement;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
});

it('shows earned and all active achievements on the settings page', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    $earned = Achievement::factory()->create(['is_active' => true]);
    $unearned = Achievement::factory()->create(['is_active' => true]);
    $inactive = Achievement::factory()->create(['is_active' => false]);

    $user->achievements()->attach($earned, ['earned_at' => now()]);

    $this->actingAs($user)
        ->get('/settings/achievements')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('settings/Achievements')
            ->has('earnedAchievements', 1)
            ->has('allAchievements', 2)
            ->where('earnedAchievements.0.id', $earned->id)
        );
});

it('excludes inactive achievements from all achievements', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    Achievement::factory()->create(['is_active' => true]);
    Achievement::factory()->create(['is_active' => false]);

    $this->actingAs($user)
        ->get('/settings/achievements')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('earnedAchievements', 0)
            ->has('allAchievements', 1)
        );
});
