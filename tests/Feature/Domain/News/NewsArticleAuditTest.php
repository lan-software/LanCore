<?php

use App\Domain\News\Models\NewsArticle;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('allows admins to view the audit page for a news article', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $article = NewsArticle::factory()->create();

    $this->actingAs($admin)
        ->get("/news-admin/{$article->id}/audit")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('news/Audit')
                ->has('article')
                ->has('audits.data')
        );
});

it('denies non-admin users access to the news article audit page', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $article = NewsArticle::factory()->create();

    $this->actingAs($user)
        ->get("/news-admin/{$article->id}/audit")
        ->assertForbidden();
});

it('redirects unauthenticated users to login for news article audit', function () {
    $article = NewsArticle::factory()->create();

    $this->get("/news-admin/{$article->id}/audit")
        ->assertRedirect('/login');
});
