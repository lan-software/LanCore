<?php

namespace App\Http\Controllers\Settings;

use App\Domain\Notification\Events\ProfileUpdated;
use App\Domain\Profile\Actions\NormalizeAvatar;
use App\Domain\Profile\Actions\NormalizeBanner;
use App\Domain\Profile\Enums\AvatarSource;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileMediaRequest;
use App\Support\StorageRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Handles avatar and banner uploads. Multipart upload endpoints are kept
 * separate from the JSON profile-update form so the latter stays small
 * and easy to validate.
 *
 * @see docs/mil-std-498/SRS.md USR-F-024
 * @see docs/mil-std-498/SSS.md SEC-022
 */
class ProfileMediaController extends Controller
{
    public function __construct(
        private readonly NormalizeAvatar $normalizeAvatar,
        private readonly NormalizeBanner $normalizeBanner,
    ) {}

    public function uploadAvatar(ProfileMediaRequest $request): RedirectResponse
    {
        $user = $request->user();
        $path = $this->normalizeAvatar->execute($user, $request->file('image'));

        $user->forceFill([
            'avatar_source' => AvatarSource::Custom,
            'avatar_path' => $path,
            'profile_updated_at' => now(),
        ])->save();

        ProfileUpdated::dispatch($user);

        return back();
    }

    public function destroyAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->avatar_path !== null) {
            StorageRole::public()->delete($user->avatar_path);
        }

        $user->forceFill([
            'avatar_source' => AvatarSource::Default,
            'avatar_path' => null,
            'profile_updated_at' => now(),
        ])->save();

        ProfileUpdated::dispatch($user);

        return back();
    }

    public function uploadBanner(ProfileMediaRequest $request): RedirectResponse
    {
        $user = $request->user();
        $path = $this->normalizeBanner->execute($user, $request->file('image'));

        $user->forceFill([
            'banner_path' => $path,
            'profile_updated_at' => now(),
        ])->save();

        ProfileUpdated::dispatch($user);

        return back();
    }

    public function destroyBanner(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->banner_path !== null) {
            StorageRole::public()->delete($user->banner_path);
        }

        $user->forceFill([
            'banner_path' => null,
            'profile_updated_at' => now(),
        ])->save();

        ProfileUpdated::dispatch($user);

        return back();
    }
}
