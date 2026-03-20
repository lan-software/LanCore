<?php

use Illuminate\Support\Facades\Storage;

it('serves a file from storage through the proxy route', function () {
    Storage::fake();
    Storage::put('images/test.jpg', 'fake-image-content');

    $this->get(route('storage.file', ['path' => 'images/test.jpg']))
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'image/jpeg');
});

it('returns 404 for a file that does not exist', function () {
    Storage::fake();

    $this->get(route('storage.file', ['path' => 'images/missing.jpg']))
        ->assertNotFound();
});

it('generates a proxy url when anonymous bucket access is disabled', function () {
    config(['filesystems.disks.s3.anonymous_bucket_access' => false]);

    $url = Storage::fileUrl('events/banners/test.jpg');

    expect($url)->toBe(route('storage.file', ['path' => 'events/banners/test.jpg']));
});

it('generates a direct s3 url when anonymous bucket access is enabled', function () {
    config(['filesystems.disks.s3.anonymous_bucket_access' => true]);

    $url = Storage::fileUrl('events/banners/test.jpg');

    expect($url)->toBe(Storage::url('events/banners/test.jpg'));
});
