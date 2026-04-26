<?php

namespace App\Domain\Profile\Actions;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;

/**
 * Center-crop and resize an avatar upload to 1000×1000 WebP, stored on
 * the public disk. Deletes the user's previous custom avatar when called.
 *
 * @see docs/mil-std-498/SRS.md USR-F-024
 * @see docs/mil-std-498/SSS.md SEC-022
 */
class NormalizeAvatar
{
    public const SIZE = 1000;

    public const QUALITY = 88;

    public function execute(User $user, UploadedFile $file): string
    {
        $manager = ImageManager::usingDriver(new GdDriver);
        $image = $manager->decodePath($file->getRealPath());

        $image->coverDown(self::SIZE, self::SIZE);
        $encoded = $image->encodeUsingFileExtension('webp', quality: self::QUALITY);

        $path = sprintf('avatars/%d_%s.webp', $user->getKey(), Str::random(8));
        Storage::disk('public')->put($path, (string) $encoded);

        $previous = $user->avatar_path;
        if ($previous !== null && $previous !== $path) {
            Storage::disk('public')->delete($previous);
        }

        return $path;
    }
}
