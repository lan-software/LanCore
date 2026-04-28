<?php

namespace App\Domain\Auth\Steam\Http\Controllers;

use App\Domain\Auth\Steam\Actions\UnlinkSteamAccount;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Authenticated link/unlink endpoints. The link redirect bounces the
 * user back through {@see SteamAuthController::callback()}, which checks
 * the {@see SteamAuthController::LINK_INTENT_KEY} session flag to
 * distinguish link from sign-in.
 */
class SteamLinkController
{
    /**
     * Inertia POSTs this endpoint via XHR, so a plain 302 to steamcommunity.com
     * triggers a CORS preflight on Steam and fails. Inertia::location() wraps the
     * response so the Inertia client does a full window.location navigation
     * instead — Steam never sees an XHR.
     */
    public function link(Request $request): SymfonyResponse
    {
        $request->session()->put(SteamAuthController::LINK_INTENT_KEY, true);

        /** @var SymfonyRedirectResponse $steamRedirect */
        $steamRedirect = Socialite::driver('steam')->redirect();

        return Inertia::location($steamRedirect->getTargetUrl());
    }

    public function unlink(
        Request $request,
        UnlinkSteamAccount $unlinker,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();

        try {
            $unlinker->execute($user);
        } catch (ValidationException $e) {
            return redirect()
                ->route('settings.linked-accounts.edit')
                ->withErrors($e->errors());
        }

        return redirect()
            ->route('settings.linked-accounts.edit')
            ->with('status', 'steam-unlinked');
    }
}
