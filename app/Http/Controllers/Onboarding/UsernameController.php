<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Http\Requests\OnboardingUsernameRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * One-time username selection screen for users registered before
 * USR-F-022 shipped. New signups already supply a username and never
 * land here.
 *
 * @see docs/mil-std-498/SRS.md USR-F-022
 */
class UsernameController extends Controller
{
    public function show(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        if ($user->username !== null) {
            return redirect()->intended(route('dashboard'));
        }

        return Inertia::render('onboarding/Username');
    }

    public function update(OnboardingUsernameRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->forceFill([
            'username' => $request->validated('username'),
            'profile_updated_at' => now(),
        ])->save();

        return redirect()->intended(route('dashboard'));
    }
}
