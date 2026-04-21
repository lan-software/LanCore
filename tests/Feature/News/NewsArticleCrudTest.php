<?php

use App\Domain\News\Models\NewsArticle;
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

it('allows admins to view the create article page', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->get('/news-admin/create')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('news/Create'));
});

it('allows admins to store a new article', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/news-admin', [
            'title' => 'Test News Article',
            'summary' => 'A short summary.',
            'content' => '<p>Article body content.</p>',
            'visibility' => 'draft',
            'tags' => ['update', 'event'],
        ])
        ->assertRedirect('/news-admin');

    expect(NewsArticle::where('title', 'Test News Article')->exists())->toBeTrue();
    expect(NewsArticle::where('slug', 'test-news-article')->exists())->toBeTrue();
});

it('allows admins to store an article with an image', function () {
    Storage::fake('public');
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/news-admin', [
            'title' => 'Article With Image',
            'visibility' => 'public',
            'published_at' => now()->toDateTimeString(),
            'image' => UploadedFile::fake()->image('news.jpg', 800, 600),
        ])
        ->assertRedirect('/news-admin');

    $article = NewsArticle::where('title', 'Article With Image')->first();
    expect($article)->not->toBeNull();
    expect($article->image)->not->toBeNull();
    Storage::disk('public')->assertExists($article->image);
});

it('generates unique slugs for articles with the same title', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/news-admin', [
            'title' => 'Duplicate Title',
            'visibility' => 'draft',
        ]);

    $this->actingAs($admin)
        ->post('/news-admin', [
            'title' => 'Duplicate Title',
            'visibility' => 'draft',
        ]);

    $articles = NewsArticle::where('title', 'Duplicate Title')->pluck('slug')->all();
    expect($articles)->toHaveCount(2);
    expect($articles[0])->not->toBe($articles[1]);
});

it('validates required fields when storing an article', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/news-admin', [])
        ->assertSessionHasErrors(['title', 'visibility']);
});

it('allows admins to view the edit article page', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $article = NewsArticle::factory()->create();

    $this->actingAs($admin)
        ->get("/news-admin/{$article->id}")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('news/Edit')
                ->has('article')
                ->where('article.id', $article->id)
        );
});

it('allows admins to update an article', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $article = NewsArticle::factory()->create();

    $this->actingAs($admin)
        ->post("/news-admin/{$article->id}", [
            'title' => 'Updated Title',
            'visibility' => 'public',
            'published_at' => now()->toDateTimeString(),
        ])
        ->assertRedirect();

    expect($article->fresh())
        ->title->toBe('Updated Title')
        ->visibility->value->toBe('public');
});

it('allows admins to archive an article', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $article = NewsArticle::factory()->published()->create();

    $this->actingAs($admin)
        ->post("/news-admin/{$article->id}", [
            'title' => $article->title,
            'visibility' => $article->visibility->value,
            'is_archived' => true,
        ])
        ->assertRedirect();

    expect($article->fresh()->is_archived)->toBeTrue();
});

it('allows admins to delete an article', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $article = NewsArticle::factory()->create();

    $this->actingAs($admin)
        ->delete("/news-admin/{$article->id}")
        ->assertRedirect('/news-admin');

    expect(NewsArticle::find($article->id))->toBeNull();
});

it('deletes the image when deleting an article', function () {
    Storage::fake('public');
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $imagePath = UploadedFile::fake()->image('news.jpg')->store('news/images', 'public');
    $article = NewsArticle::factory()->create(['image' => $imagePath]);

    Storage::disk('public')->assertExists($imagePath);

    $this->actingAs($admin)
        ->delete("/news-admin/{$article->id}");

    Storage::disk('public')->assertMissing($imagePath);
});

it('forbids users from creating articles', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->post('/news-admin', [
            'title' => 'Test',
            'visibility' => 'draft',
        ])
        ->assertForbidden();
});

it('sets the authenticated user as the author', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/news-admin', [
            'title' => 'Authored Article',
            'visibility' => 'draft',
        ])
        ->assertRedirect('/news-admin');

    $article = NewsArticle::where('title', 'Authored Article')->first();
    expect($article->author_id)->toBe($admin->id);
});

it('sets published_at server-side when publish_now is true', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->freezeTime();

    $this->actingAs($admin)
        ->post('/news-admin', [
            'title' => 'Publish Now Article',
            'visibility' => 'public',
            'publish_now' => true,
        ])
        ->assertRedirect('/news-admin');

    $article = NewsArticle::where('title', 'Publish Now Article')->first();
    expect($article->published_at)->not->toBeNull();
    expect($article->published_at->toDateTimeString())->toBe(now()->toDateTimeString());
});

it('does not set published_at when publish_now is false', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/news-admin', [
            'title' => 'No Publish Date Article',
            'visibility' => 'draft',
            'publish_now' => false,
        ])
        ->assertRedirect('/news-admin');

    $article = NewsArticle::where('title', 'No Publish Date Article')->first();
    expect($article->published_at)->toBeNull();
});
