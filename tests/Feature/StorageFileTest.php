<?php

use App\Support\StorageRole;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

it('serves a file from the public disk through the proxy route', function () {
    Storage::fake('public');
    Storage::disk('public')->put('images/test.jpg', 'fake-image-content');

    $this->get(route('storage.file', ['path' => 'images/test.jpg']))
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'image/jpeg');
});

it('returns 404 when the requested file is not on the public disk', function () {
    Storage::fake('public');

    $this->get(route('storage.file', ['path' => 'images/missing.jpg']))
        ->assertNotFound();
});

it('publicUrl proxies through the storage.file route for local public disks', function () {
    Config::set('filesystems.public_disk', 'public');

    $url = StorageRole::publicUrl('events/banners/test.jpg');

    // Local public disks must NOT use disk()->url() because that produces
    // /storage/{path} which collides with the local disk's auto-registered
    // signed URL route (serve: true) and returns 403 for unsigned requests.
    expect($url)->toBe(route('storage.file', ['path' => 'events/banners/test.jpg']));
});

it('publicUrl returns a direct url for s3 public buckets with anonymous access', function () {
    Config::set('filesystems.public_disk', 's3_public');
    Config::set('filesystems.disks.s3_public.anonymous_bucket_access', true);
    Storage::fake('s3_public');

    $url = StorageRole::publicUrl('events/banners/test.jpg');

    expect($url)->toBe(Storage::disk('s3_public')->url('events/banners/test.jpg'));
});

it('publicUrl proxies through the app for s3 public buckets without anonymous access', function () {
    Config::set('filesystems.public_disk', 's3_public');
    Config::set('filesystems.disks.s3_public.anonymous_bucket_access', false);

    $url = StorageRole::publicUrl('events/banners/test.jpg');

    expect($url)->toBe(route('storage.file', ['path' => 'events/banners/test.jpg']));
});
