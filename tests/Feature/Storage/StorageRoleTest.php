<?php

use App\Support\StorageRole;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

it('returns the configured public and private disks', function () {
    Config::set('filesystems.public_disk', 'public');
    Config::set('filesystems.private_disk', 'local');

    expect(StorageRole::publicDiskName())->toBe('public');
    expect(StorageRole::privateDiskName())->toBe('local');
});

it('routes the public role to a custom disk when configured', function () {
    Config::set('filesystems.public_disk', 's3_public');
    Storage::fake('s3_public');

    StorageRole::public()->put('logos/test.png', 'fake-png');

    Storage::disk('s3_public')->assertExists('logos/test.png');
});

it('routes the private role to a custom disk when configured', function () {
    Config::set('filesystems.private_disk', 's3_private');
    Storage::fake('s3_private');

    StorageRole::private()->put('invoices/1.pdf', 'fake-pdf');

    Storage::disk('s3_private')->assertExists('invoices/1.pdf');
});
