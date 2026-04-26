<?php

namespace App\Domain\Profile\Actions;

use App\Models\User;
use App\Support\StorageRole;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;

/**
 * Center-crop and resize a banner upload to 1500×500 (3:1) WebP, stored
 * on the public disk. Deletes the user's previous banner when called.
 *
 * @see docs/mil-std-498/SRS.md USR-F-024
 * @see docs/mil-std-498/SSS.md SEC-022
 */
class NormalizeBanner
{
    public const WIDTH = 1500;

    public const HEIGHT = 500;

    public const QUALITY = 86;

    public function execute(User $user, UploadedFile $file): string
    {
        $manager = ImageManager::usingDriver(new GdDriver);
        $image = $manager->decodePath($file->getRealPath());

        $image->coverDown(self::WIDTH, self::HEIGHT);
        $encoded = $image->encodeUsingFileExtension('webp', quality: self::QUALITY);

        $path = sprintf('banners/%d_%s.webp', $user->getKey(), Str::random(8));
        $disk = StorageRole::public();
        $disk->put($path, (string) $encoded);

        $previous = $user->banner_path;
        if ($previous !== null && $previous !== $path) {
            $disk->delete($previous);
        }

        return $path;
    }
}
