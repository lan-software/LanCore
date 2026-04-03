<?php

use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Moderator->value], ['label' => 'Moderator']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
    Role::updateOrCreate(['name' => RoleName::SponsorManager->value], ['label' => 'Sponsor Manager']);
});

// --- Superadmin can access everything ---

it('allows superadmin to access all admin routes', function (string $route) {
    $user = User::factory()->withRole(RoleName::Superadmin)->create();

    $this->actingAs($user)->get($route)->assertSuccessful();
})->with([
    '/achievements-admin',
    '/announcements-admin',
    '/events',
    '/programs',
    '/venues',
    '/games',
    '/seat-plans',
    '/ticket-types',
    '/webhooks-admin',
    '/integrations',
    '/users',
    '/orders',
    '/news-admin',
    '/news-admin/comments',
    '/sponsors',
    '/sponsor-levels',
]);

// --- Admin can access all admin routes ---

it('allows admin to access admin routes', function (string $route) {
    $user = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($user)->get($route)->assertSuccessful();
})->with([
    '/achievements-admin',
    '/announcements-admin',
    '/events',
    '/programs',
    '/venues',
    '/games',
    '/seat-plans',
    '/ticket-types',
    '/webhooks-admin',
    '/integrations',
    '/users',
    '/orders',
    '/news-admin',
    '/news-admin/comments',
    '/sponsors',
    '/sponsor-levels',
]);

// --- Moderator can access content routes ---

it('allows moderator to access content moderation routes', function (string $route) {
    $user = User::factory()->withRole(RoleName::Moderator)->create();

    $this->actingAs($user)->get($route)->assertSuccessful();
})->with([
    '/announcements-admin',
    '/news-admin/comments',
]);

it('blocks moderator from non-content admin routes', function (string $route) {
    $user = User::factory()->withRole(RoleName::Moderator)->create();

    $this->actingAs($user)->get($route)->assertForbidden();
})->with([
    '/achievements-admin',
    '/events',
    '/venues',
    '/webhooks-admin',
    '/integrations',
    '/users',
    '/orders',
    '/seat-plans',
    '/ticket-types',
    '/sponsor-levels',
]);

// --- Regular user is blocked from admin routes ---

it('blocks regular user from admin routes', function (string $route) {
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)->get($route)->assertForbidden();
})->with([
    '/achievements-admin',
    '/announcements-admin',
    '/events',
    '/programs',
    '/venues',
    '/games',
    '/seat-plans',
    '/ticket-types',
    '/webhooks-admin',
    '/integrations',
    '/users',
    '/orders',
    '/news-admin',
    '/news-admin/comments',
    '/sponsors',
    '/sponsor-levels',
]);

// --- Sponsor manager can access sponsors ---

it('allows sponsor manager to view sponsors list', function () {
    $user = User::factory()->withRole(RoleName::SponsorManager)->create();

    $this->actingAs($user)->get('/sponsors')->assertSuccessful();
});

it('blocks sponsor manager from non-sponsor admin routes', function (string $route) {
    $user = User::factory()->withRole(RoleName::SponsorManager)->create();

    $this->actingAs($user)->get($route)->assertForbidden();
})->with([
    '/achievements-admin',
    '/events',
    '/venues',
    '/webhooks-admin',
    '/integrations',
    '/users',
    '/orders',
]);
