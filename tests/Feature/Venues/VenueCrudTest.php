<?php

use App\Domain\Venue\Models\Venue;
use App\Domain\Venue\Models\VenueImage;
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

it('allows admins to view the create venue page', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->get('/venues/create')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('venues/Create'));
});

it('allows admins to store a new venue', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/venues', [
            'name' => 'Test Venue',
            'description' => 'A great venue',
            'street' => '123 Main St',
            'city' => 'Springfield',
            'zip_code' => '12345',
            'country' => 'US',
        ])
        ->assertRedirect('/venues');

    expect(Venue::where('name', 'Test Venue')->exists())->toBeTrue();
});

it('validates required fields when storing a venue', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/venues', [])
        ->assertSessionHasErrors(['name', 'street', 'city', 'zip_code', 'country']);
});

it('allows admins to view the edit venue page', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $venue = Venue::factory()->create();

    $this->actingAs($admin)
        ->get("/venues/{$venue->id}")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('venues/Edit')
                ->has('venue')
                ->where('venue.id', $venue->id)
        );
});

it('allows admins to update a venue', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $venue = Venue::factory()->create();

    $this->actingAs($admin)
        ->patch("/venues/{$venue->id}", [
            'name' => 'Updated Venue',
            'street' => '456 Elm St',
            'city' => 'Shelbyville',
            'zip_code' => '67890',
            'country' => 'US',
        ])
        ->assertRedirect();

    expect($venue->fresh()->name)->toBe('Updated Venue');
});

it('allows admins to delete a venue', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $venue = Venue::factory()->create();

    $this->actingAs($admin)
        ->delete("/venues/{$venue->id}")
        ->assertRedirect('/venues');

    expect(Venue::find($venue->id))->toBeNull();
});

it('forbids users from creating venues', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->post('/venues', [
            'name' => 'Test',
            'street' => '1 St',
            'city' => 'City',
            'zip_code' => '00000',
            'country' => 'US',
        ])
        ->assertForbidden();
});

it('stores venue images when creating a venue', function () {
    Storage::fake('public');
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/venues', [
            'name' => 'Venue With Images',
            'street' => '123 Main St',
            'city' => 'Springfield',
            'zip_code' => '12345',
            'country' => 'US',
            'images' => [
                ['file' => UploadedFile::fake()->image('photo1.jpg', 800, 600), 'alt_text' => 'Main hall'],
                ['file' => UploadedFile::fake()->image('photo2.png', 640, 480), 'alt_text' => ''],
            ],
        ])
        ->assertRedirect('/venues');

    $venue = Venue::where('name', 'Venue With Images')->first();
    expect($venue->images)->toHaveCount(2);
    expect($venue->images[0]->alt_text)->toBe('Main hall');

    foreach ($venue->images as $image) {
        Storage::disk('public')->assertExists($image->path);
    }
});

it('keeps existing images and adds new ones when updating a venue', function () {
    Storage::fake('public');
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $oldFile = UploadedFile::fake()->image('old.jpg');
    $oldPath = $oldFile->store('venues/images', 'public');

    $venue = Venue::factory()->create();
    $existingImage = VenueImage::create([
        'venue_id' => $venue->id,
        'path' => $oldPath,
        'alt_text' => 'Old photo',
        'sort_order' => 0,
    ]);

    $this->actingAs($admin)
        ->patch("/venues/{$venue->id}", [
            'name' => $venue->name,
            'street' => $venue->address->street,
            'city' => $venue->address->city,
            'zip_code' => $venue->address->zip_code,
            'country' => $venue->address->country,
            'existing_images' => [
                ['id' => $existingImage->id, 'alt_text' => 'Updated alt'],
            ],
            'new_images' => [
                ['file' => UploadedFile::fake()->image('new.jpg', 800, 600), 'alt_text' => 'New photo'],
            ],
        ])
        ->assertRedirect();

    $venue->refresh();
    expect($venue->images)->toHaveCount(2);
    expect($venue->images[0]->alt_text)->toBe('Updated alt');
    Storage::disk('public')->assertExists($oldPath);
    Storage::disk('public')->assertExists($venue->images[1]->path);
});

it('deletes removed images from storage when updating a venue', function () {
    Storage::fake('public');
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $file = UploadedFile::fake()->image('delete-me.jpg');
    $path = $file->store('venues/images', 'public');

    $venue = Venue::factory()->create();
    VenueImage::create([
        'venue_id' => $venue->id,
        'path' => $path,
        'alt_text' => null,
        'sort_order' => 0,
    ]);

    $this->actingAs($admin)
        ->patch("/venues/{$venue->id}", [
            'name' => $venue->name,
            'street' => $venue->address->street,
            'city' => $venue->address->city,
            'zip_code' => $venue->address->zip_code,
            'country' => $venue->address->country,
        ])
        ->assertRedirect();

    expect($venue->fresh()->images)->toHaveCount(0);
    Storage::disk('public')->assertMissing($path);
});

it('deletes venue images from storage when deleting a venue', function () {
    Storage::fake('public');
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $file = UploadedFile::fake()->image('photo.jpg');
    $path = $file->store('venues/images', 'public');

    $venue = Venue::factory()->create();
    VenueImage::create([
        'venue_id' => $venue->id,
        'path' => $path,
        'alt_text' => null,
        'sort_order' => 0,
    ]);

    $this->actingAs($admin)
        ->delete("/venues/{$venue->id}")
        ->assertRedirect('/venues');

    Storage::disk('public')->assertMissing($path);
});

it('rejects non-image files for venue images', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/venues', [
            'name' => 'Bad Upload Venue',
            'street' => '1 St',
            'city' => 'City',
            'zip_code' => '00000',
            'country' => 'US',
            'images' => [
                ['file' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'), 'alt_text' => ''],
            ],
        ])
        ->assertSessionHasErrors(['images.0.file']);
});
