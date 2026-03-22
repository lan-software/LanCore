<?php

use App\Domain\News\Models\NewsComment;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('allows admins to view the audit page for a news comment', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $comment = NewsComment::factory()->create();

    $this->actingAs($admin)
        ->get("/news/comments/{$comment->id}/audit")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('news/comments/Audit')
                ->has('comment')
                ->has('audits.data')
        );
});

it('denies non-admin users access to the news comment audit page', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $comment = NewsComment::factory()->create();

    $this->actingAs($user)
        ->get("/news/comments/{$comment->id}/audit")
        ->assertForbidden();
});

it('redirects unauthenticated users to login for news comment audit', function () {
    $comment = NewsComment::factory()->create();

    $this->get("/news/comments/{$comment->id}/audit")
        ->assertRedirect('/login');
});
