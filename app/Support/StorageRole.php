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

    /**
     * Return a URL for a file on the public disk.
     *
     * Local disks and S3 buckets with anonymous access return a direct URL.
     * Private S3 buckets (no anonymous access) are proxied through the
     * `storage.file` route so callers never have to think about disk driver.
     */
    public static function publicUrl(string $path): string
    {
        $disk = self::publicDiskName();
        $driver = (string) config("filesystems.disks.{$disk}.driver", '');
        $anonymousAccess = (bool) config("filesystems.disks.{$disk}.anonymous_bucket_access", false);

        if ($driver === 'local' || ($driver === 's3' && $anonymousAccess)) {
            return self::public()->url($path);
        }

        return route('storage.file', ['path' => $path]);
    }
}
