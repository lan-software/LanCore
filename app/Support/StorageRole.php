<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * Resolves the configured semantic storage roles.
 *
 * Public role is used for non-sensitive assets (logos, public images).
 * Private role is used for sensitive artifacts (invoices, receipts, ticket PDFs).
 */
class StorageRole
{
    public static function public(): Filesystem
    {
        return Storage::disk(self::publicDiskName());
    }

    public static function private(): Filesystem
    {
        return Storage::disk(self::privateDiskName());
    }

    public static function publicDiskName(): string
    {
        return (string) config('filesystems.public_disk', 'public');
    }

    public static function privateDiskName(): string
    {
        return (string) config('filesystems.private_disk', 'local');
    }
}
