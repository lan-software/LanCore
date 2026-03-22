<?php

use App\Domain\News\Actions\CreateNewsArticle;
use App\Domain\News\Actions\UpdateNewsArticle;
use App\Domain\News\Events\NewsArticlePublished;
use App\Domain\News\Models\NewsArticle;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
});

it('dispatches NewsArticlePublished event when creating a published article with notify_users', function () {
    Event::fake([NewsArticlePublished::class]);

    $action = app(CreateNewsArticle::class);
    $action->execute([
        'title' => 'Test Article',
        'visibility' => 'public',
        'notify_users' => true,
        'published_at' => now()->toDateTimeString(),
        'author_id' => User::factory()->create()->id,
    ]);

    Event::assertDispatched(NewsArticlePublished::class);
});

it('does not dispatch event when creating a draft article with notify_users', function () {
    Event::fake([NewsArticlePublished::class]);

    $action = app(CreateNewsArticle::class);
    $action->execute([
        'title' => 'Draft Article',
        'visibility' => 'draft',
        'notify_users' => true,
        'published_at' => null,
        'author_id' => User::factory()->create()->id,
    ]);

    Event::assertNotDispatched(NewsArticlePublished::class);
});

it('does not dispatch event when creating a published article without notify_users', function () {
    Event::fake([NewsArticlePublished::class]);

    $action = app(CreateNewsArticle::class);
    $action->execute([
        'title' => 'No Notify Article',
        'visibility' => 'public',
        'notify_users' => false,
        'published_at' => now()->toDateTimeString(),
        'author_id' => User::factory()->create()->id,
    ]);

    Event::assertNotDispatched(NewsArticlePublished::class);
});

it('dispatches event when updating a draft article to published with notify_users', function () {
    Event::fake([NewsArticlePublished::class]);

    $article = NewsArticle::factory()->create([
        'notify_users' => true,
        'published_at' => null,
    ]);

    $action = app(UpdateNewsArticle::class);
    $action->execute($article, [
        'published_at' => now()->toDateTimeString(),
    ]);

    Event::assertDispatched(NewsArticlePublished::class);
});

it('dispatches event when enabling notify_users on already published article', function () {
    Event::fake([NewsArticlePublished::class]);

    $article = NewsArticle::factory()->published()->create([
        'notify_users' => false,
    ]);

    $action = app(UpdateNewsArticle::class);
    $action->execute($article, [
        'notify_users' => true,
    ]);

    Event::assertDispatched(NewsArticlePublished::class);
});

it('does not dispatch event when updating a published article that already had notify_users', function () {
    Event::fake([NewsArticlePublished::class]);

    $article = NewsArticle::factory()->published()->create([
        'notify_users' => true,
    ]);

    $action = app(UpdateNewsArticle::class);
    $action->execute($article, [
        'title' => 'Updated Title',
    ]);

    Event::assertNotDispatched(NewsArticlePublished::class);
});

it('allows admins to store a new article with notify_users flag', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/news-admin', [
            'title' => 'Notify Users Article',
            'visibility' => 'public',
            'notify_users' => true,
            'published_at' => now()->toDateTimeString(),
        ])
        ->assertRedirect('/news-admin');

    $article = NewsArticle::where('title', 'Notify Users Article')->first();
    expect($article)->not->toBeNull();
    expect($article->notify_users)->toBeTrue();
});
