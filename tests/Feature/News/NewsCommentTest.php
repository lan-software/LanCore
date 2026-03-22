<?php

use App\Domain\News\Models\NewsArticle;
use App\Domain\News\Models\NewsComment;
use App\Domain\News\Models\NewsCommentVote;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('allows authenticated users to post a comment', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $article = NewsArticle::factory()->published()->create([
        'comments_enabled' => true,
        'comments_require_approval' => false,
    ]);

    $this->actingAs($user)
        ->post("/news/{$article->id}/comments", [
            'content' => 'This is my comment.',
        ])
        ->assertRedirect();

    $comment = NewsComment::where('content', 'This is my comment.')->first();
    expect($comment)->not->toBeNull();
    expect($comment->user_id)->toBe($user->id);
    expect($comment->is_approved)->toBeTrue();
});

it('auto-approves comments when approval is not required', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $article = NewsArticle::factory()->published()->create([
        'comments_enabled' => true,
        'comments_require_approval' => false,
    ]);

    $this->actingAs($user)
        ->post("/news/{$article->id}/comments", [
            'content' => 'Auto-approved comment.',
        ]);

    $comment = NewsComment::where('content', 'Auto-approved comment.')->first();
    expect($comment->is_approved)->toBeTrue();
});

it('requires approval when comments_require_approval is enabled', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $article = NewsArticle::factory()->published()->create([
        'comments_enabled' => true,
        'comments_require_approval' => true,
    ]);

    $this->actingAs($user)
        ->post("/news/{$article->id}/comments", [
            'content' => 'Pending comment.',
        ]);

    $comment = NewsComment::where('content', 'Pending comment.')->first();
    expect($comment->is_approved)->toBeFalse();
});

it('prevents comments when comments are disabled', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $article = NewsArticle::factory()->published()->create([
        'comments_enabled' => false,
    ]);

    $this->actingAs($user)
        ->post("/news/{$article->id}/comments", [
            'content' => 'Should not work.',
        ])
        ->assertForbidden();
});

it('redirects unauthenticated users trying to comment', function () {
    $article = NewsArticle::factory()->published()->create();

    $this->post("/news/{$article->id}/comments", [
        'content' => 'Anonymous comment.',
    ])->assertRedirectToRoute('login');
});

it('validates comment content is required', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $article = NewsArticle::factory()->published()->create(['comments_enabled' => true]);

    $this->actingAs($user)
        ->post("/news/{$article->id}/comments", [])
        ->assertSessionHasErrors(['content']);
});

it('allows admins to approve a comment', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $comment = NewsComment::factory()->create(['is_approved' => false]);

    $this->actingAs($admin)
        ->post("/news/comments/{$comment->id}/approve")
        ->assertRedirect();

    expect($comment->fresh()->is_approved)->toBeTrue();
});

it('allows admins to delete a comment', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $comment = NewsComment::factory()->create();

    $this->actingAs($admin)
        ->delete("/news/comments/{$comment->id}")
        ->assertRedirect();

    expect(NewsComment::find($comment->id))->toBeNull();
});

it('forbids regular users from deleting comments', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $comment = NewsComment::factory()->create();

    $this->actingAs($user)
        ->delete("/news/comments/{$comment->id}")
        ->assertForbidden();
});

it('allows authenticated users to upvote a comment', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $comment = NewsComment::factory()->create(['is_approved' => true]);

    $this->actingAs($user)
        ->post("/news/comments/{$comment->id}/vote", ['value' => 1])
        ->assertRedirect();

    $vote = NewsCommentVote::where('news_comment_id', $comment->id)
        ->where('user_id', $user->id)
        ->first();
    expect($vote)->not->toBeNull();
    expect($vote->value)->toBe(1);
});

it('allows authenticated users to downvote a comment', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $comment = NewsComment::factory()->create(['is_approved' => true]);

    $this->actingAs($user)
        ->post("/news/comments/{$comment->id}/vote", ['value' => -1])
        ->assertRedirect();

    $vote = NewsCommentVote::where('news_comment_id', $comment->id)
        ->where('user_id', $user->id)
        ->first();
    expect($vote->value)->toBe(-1);
});

it('removes vote when voting the same value again', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $comment = NewsComment::factory()->create(['is_approved' => true]);

    // Vote up
    $this->actingAs($user)
        ->post("/news/comments/{$comment->id}/vote", ['value' => 1]);

    // Vote up again removes the vote
    $this->actingAs($user)
        ->post("/news/comments/{$comment->id}/vote", ['value' => 1]);

    expect(NewsCommentVote::where('news_comment_id', $comment->id)
        ->where('user_id', $user->id)
        ->exists())->toBeFalse();
});

it('changes vote when voting a different value', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $comment = NewsComment::factory()->create(['is_approved' => true]);

    // Vote up
    $this->actingAs($user)
        ->post("/news/comments/{$comment->id}/vote", ['value' => 1]);

    // Vote down changes the vote
    $this->actingAs($user)
        ->post("/news/comments/{$comment->id}/vote", ['value' => -1]);

    $vote = NewsCommentVote::where('news_comment_id', $comment->id)
        ->where('user_id', $user->id)
        ->first();
    expect($vote->value)->toBe(-1);
});

it('rejects invalid vote values', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $comment = NewsComment::factory()->create(['is_approved' => true]);

    $this->actingAs($user)
        ->post("/news/comments/{$comment->id}/vote", ['value' => 5])
        ->assertStatus(422);
});
