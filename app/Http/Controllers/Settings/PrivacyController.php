<?php

namespace App\Http\Controllers\Settings;

use App\Domain\Notification\Events\ProfileUpdated;
use App\Domain\Profile\Enums\ProfileVisibility;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @see docs/mil-std-498/SRS.md SET-F-009, SET-F-010, USR-F-025
 */
class PrivacyController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('settings/Privacy', [
            'isSeatVisiblePublicly' => (bool) $user->is_seat_visible_publicly,
            'profileVisibility' => ($user->profile_visibility instanceof ProfileVisibility ? $user->profile_visibility : ProfileVisibility::LoggedIn)->value,
            'profileVisibilities' => array_map(fn (ProfileVisibility $v) => $v->value, ProfileVisibility::cases()),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'is_seat_visible_publicly' => ['sometimes', 'boolean'],
            'profile_visibility' => ['sometimes', new Enum(ProfileVisibility::class)],
        ]);

        $user = $request->user();
        $user->update($validated);

        if ($user->wasChanged('profile_visibility')) {
            $user->forceFill(['profile_updated_at' => now()])->save();
            ProfileUpdated::dispatch($user);
        }

        return back();
    }
}
