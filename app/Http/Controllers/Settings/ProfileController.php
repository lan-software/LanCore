<?php

namespace App\Http\Controllers\Settings;

use App\Domain\Notification\Events\ProfileUpdated;
use App\Domain\Profile\Enums\AvatarSource;
use App\Domain\Profile\Enums\ProfileVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('settings/Profile', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            'profileAlert' => $request->session()->get('profileAlert'),
            'profile' => [
                'username' => $user->username,
                'short_bio' => $user->short_bio,
                'profile_description' => $user->profile_description,
                'profile_emoji' => $user->profile_emoji,
                'avatar_source' => ($user->avatar_source instanceof AvatarSource ? $user->avatar_source : AvatarSource::Default)->value,
                'avatar_url' => $user->avatarUrl(),
                'banner_url' => $user->bannerUrl(),
                'profile_visibility' => ($user->profile_visibility instanceof ProfileVisibility ? $user->profile_visibility : ProfileVisibility::LoggedIn)->value,
            ],
            'avatarSources' => array_map(fn (AvatarSource $s) => $s->value, AvatarSource::cases()),
            'profileVisibilities' => array_map(fn (ProfileVisibility $v) => $v->value, ProfileVisibility::cases()),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($user->isDirty(['username', 'short_bio', 'profile_description', 'profile_emoji', 'avatar_source'])) {
            $user->profile_updated_at = now();
        }

        $user->save();

        ProfileUpdated::dispatch($user);

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
