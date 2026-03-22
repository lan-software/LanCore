<?php

use App\Domain\News\Models\NewsArticle;
use App\Domain\News\Models\NewsComment;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('redirects unauthenticated users from comments admin', function () {
    $this->get('/news-admin/comments')
        ->assertRedirectToRoute('login');
});

it('forbids regular users from accessing comments admin', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->get('/news-admin/comments')
        ->assertForbidden();
});

it('allows admins to access comments admin', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    NewsComment::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get('/news-admin/comments')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('news/comments/Index')
            ->has('comments.data', 3)
            ->has('articles')
            ->has('tags')
            ->has('filters'));
});

it('filters comments by article', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $article = NewsArticle::factory()->published()->create();
    NewsComment::factory()->count(2)->create(['news_article_id' => $article->id]);
    NewsComment::factory()->create(); // different article

    $this->actingAs($admin)
        ->get('/news-admin/comments?article_id='.$article->id)
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('comments.data', 2));
});

it('filters comments by approval status', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    NewsComment::factory()->count(2)->create(['is_approved' => true]);
    NewsComment::factory()->count(3)->unapproved()->create();

    $this->actingAs($admin)
        ->get('/news-admin/comments?is_approved=0')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('comments.data', 3));
});

it('filters comments by article visibility', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $publicArticle = NewsArticle::factory()->published()->create(['visibility' => 'public']);
    $draftArticle = NewsArticle::factory()->create(['visibility' => 'draft']);
    NewsComment::factory()->count(2)->create(['news_article_id' => $publicArticle->id]);
    NewsComment::factory()->create(['news_article_id' => $draftArticle->id]);

    $this->actingAs($admin)
        ->get('/news-admin/comments?visibility=public')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('comments.data', 2));
});

it('filters comments by tag', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $taggedArticle = NewsArticle::factory()->published()->create(['tags' => ['esports', 'gaming']]);
    $otherArticle = NewsArticle::factory()->published()->create(['tags' => ['music']]);
    NewsComment::factory()->count(2)->create(['news_article_id' => $taggedArticle->id]);
    NewsComment::factory()->create(['news_article_id' => $otherArticle->id]);

    $this->actingAs($admin)
        ->get('/news-admin/comments?tag=esports')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('comments.data', 2));
});

it('searches comments by content', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    NewsComment::factory()->create(['content' => 'Great article about Laravel']);
    NewsComment::factory()->create(['content' => 'Something else entirely']);

    $this->actingAs($admin)
        ->get('/news-admin/comments?search=Laravel')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('comments.data', 1));
});

it('allows admins to edit a comment from admin view', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $comment = NewsComment::factory()->create(['content' => 'Original content']);

    $this->actingAs($admin)
        ->patch("/news/comments/{$comment->id}", ['content' => 'Updated content'])
        ->assertRedirect();

    expect($comment->fresh()->content)->toBe('Updated content');
    expect($comment->fresh()->edited_at)->not->toBeNull();
});
