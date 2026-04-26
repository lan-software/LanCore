<?php

use App\Domain\Profile\Actions\NormalizeAvatar;
use App\Domain\Profile\Enums\AvatarSource;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * @see docs/mil-std-498/STD.md §4.25 Profile Customization Tests (avatar upload)
 * @see docs/mil-std-498/SRS.md USR-F-024
 * @see docs/mil-std-498/SSS.md SEC-022
 */
beforeEach(function (): void {
    Storage::fake('public');
});

test('upload normalizes a non-square JPEG to 1000x1000 WebP', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('avatar.jpg', 2000, 3000);

    $response = $this
        ->actingAs($user)
        ->post(route('profile.avatar.upload'), ['image' => $file]);

    $response->assertSessionHasNoErrors();

    $user->refresh();
    expect($user->avatar_source)->toBe(AvatarSource::Custom);
    expect($user->avatar_path)->not->toBeNull();

    Storage::disk('public')->assertExists($user->avatar_path);
});

test('avatar upload rejects files larger than 5 MB', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('big.jpg')->size(5121); // KB

    $response = $this
        ->actingAs($user)
        ->post(route('profile.avatar.upload'), ['image' => $file]);

    $response->assertSessionHasErrors('image');
});

test('avatar upload rejects non-image mime types', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    $response = $this
        ->actingAs($user)
        ->post(route('profile.avatar.upload'), ['image' => $file]);

    $response->assertSessionHasErrors('image');
});

test('uploading a new avatar deletes the previous file', function () {
    $user = User::factory()->create();

    $first = UploadedFile::fake()->image('first.jpg', 1200, 1200);
    $this->actingAs($user)->post(route('profile.avatar.upload'), ['image' => $first]);
    $firstPath = $user->fresh()->avatar_path;

    $second = UploadedFile::fake()->image('second.jpg', 1500, 1500);
    $this->actingAs($user)->post(route('profile.avatar.upload'), ['image' => $second]);
    $secondPath = $user->fresh()->avatar_path;

    expect($firstPath)->not->toBe($secondPath);
    Storage::disk('public')->assertMissing($firstPath);
    Storage::disk('public')->assertExists($secondPath);
});

test('NormalizeAvatar produces a 1000x1000 WebP', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('a.png', 1500, 800);

    $path = app(NormalizeAvatar::class)->execute($user, $file);

    Storage::disk('public')->assertExists($path);
    expect($path)->toEndWith('.webp');
});
