<?php

namespace App\Domain\Auth\Steam\Http\Controllers;

use App\Domain\Auth\Steam\Actions\CompleteSteamRegistration;
use App\Domain\Auth\Steam\Actions\LinkSteamAccount;
use App\Domain\Auth\Steam\Actions\ResolveOrPrepareSteamUser;
use App\Domain\Auth\Steam\Data\PendingSteamRegistration;
use App\Domain\Auth\Steam\Http\Requests\CompleteSteamRegistrationRequest;
use App\Domain\Policy\Models\PolicyVersion;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

/**
 * Steam OpenID 2.0 sign-in / sign-up flow.
 *
 * - redirect:     bounce the user to Steam to authenticate
 * - callback:     handle Steam's response — either log in an existing user
 *                 or stash a pending registration and bounce to /auth/steam/complete
 * - showComplete: render the profile-completion form
 * - complete:     create the user and log them in
 */
class SteamAuthController
{
    public const LINK_INTENT_KEY = 'auth.steam.link_intent';

    public function redirect(): SymfonyRedirectResponse
    {
        return Socialite::driver('steam')->redirect();
    }

    public function callback(
        Request $request,
        ResolveOrPrepareSteamUser $resolve,
        LinkSteamAccount $linker,
    ): RedirectResponse {
        try {
            $steamUser = Socialite::driver('steam')->user();
        } catch (Throwable) {
            return $this->failureRedirect($request, __('auth.steam.errors.steamApiUnreachable'));
        }

        if ($request->session()->pull(self::LINK_INTENT_KEY) === true && Auth::check()) {
            return $this->handleLinkCallback($linker, $steamUser);
        }

        $existing = $resolve->execute($steamUser);

        if ($existing !== null) {
            Auth::login($existing, remember: true);
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        $pending = PendingSteamRegistration::fromSocialiteUser($steamUser);
        $request->session()->put(PendingSteamRegistration::SESSION_KEY, $pending->toArray());

        return redirect()->route('auth.steam.complete.show');
    }

    private function handleLinkCallback(
        LinkSteamAccount $linker,
        \Laravel\Socialite\Contracts\User $steamUser,
    ): RedirectResponse {
        /** @var User $user */
        $user = Auth::user();

        try {
            $linker->execute($user, $steamUser);
        } catch (ValidationException $e) {
            return redirect()
                ->route('settings.linked-accounts.edit')
                ->withErrors($e->errors());
        }

        return redirect()
            ->route('settings.linked-accounts.edit')
            ->with('status', 'steam-linked');
    }

    private function failureRedirect(Request $request, string $message): RedirectResponse
    {
        $route = Auth::check() ? 'settings.linked-accounts.edit' : 'login';

        return redirect()->route($route)->withErrors(['steam' => $message]);
    }

    public function showComplete(
        Request $request,
        CompleteSteamRegistration $action,
    ): InertiaResponse|RedirectResponse {
        $pending = $this->pendingFromSession($request);

        if ($pending === null) {
            return redirect()->route('login')->withErrors([
                'steam' => __('auth.steam.errors.pendingExpired'),
            ]);
        }

        return Inertia::render('auth/CompleteSteamProfile', [
            'pending' => $pending->toArray(),
            'suggestedUsername' => $this->suggestUsername($pending->personaName),
            'requiredPolicies' => $this->presentPolicyVersions($action->resolveRequiredPolicyVersions()),
        ]);
    }

    public function complete(
        CompleteSteamRegistrationRequest $request,
        CompleteSteamRegistration $action,
    ): RedirectResponse {
        $pending = $this->pendingFromSession($request);

        if ($pending === null) {
            return redirect()->route('login')->withErrors([
                'steam' => __('auth.steam.errors.pendingExpired'),
            ]);
        }

        $required = $action->resolveRequiredPolicyVersions();
        $this->ensureRequiredPoliciesAccepted($required, $request->validated());

        $user = $action->execute(
            $pending,
            $request->validated(),
            $required,
            $request,
        );

        $request->session()->forget(PendingSteamRegistration::SESSION_KEY);

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    private function pendingFromSession(Request $request): ?PendingSteamRegistration
    {
        $payload = $request->session()->get(PendingSteamRegistration::SESSION_KEY);

        if (! is_array($payload) || ! isset($payload['steam_id_64'])) {
            return null;
        }

        $pending = PendingSteamRegistration::fromArray($payload);

        if ($pending->isExpired()) {
            $request->session()->forget(PendingSteamRegistration::SESSION_KEY);

            return null;
        }

        return $pending;
    }

    private function suggestUsername(?string $personaName): ?string
    {
        if ($personaName === null || $personaName === '') {
            return null;
        }

        $sanitized = preg_replace('/[^A-Za-z0-9_-]/', '', $personaName) ?? '';
        $trimmed = trim($sanitized, '_-');

        if (mb_strlen($trimmed) < 3) {
            return null;
        }

        return mb_substr($trimmed, 0, 32);
    }

    /**
     * @param  array<int, PolicyVersion>  $versions
     * @return array<int, array<string, mixed>>
     */
    private function presentPolicyVersions(array $versions): array
    {
        return array_map(fn ($version) => [
            'id' => $version->id,
            'policy_id' => $version->policy_id,
            'policy_key' => $version->policy?->key,
            'policy_name' => $version->policy?->name,
            'policy_description' => $version->policy?->description,
            'version_number' => $version->version_number,
            'locale' => $version->locale,
        ], $versions);
    }

    /**
     * @param  array<int, PolicyVersion>  $required
     * @param  array<string, mixed>  $input
     */
    private function ensureRequiredPoliciesAccepted(array $required, array $input): void
    {
        if ($required === []) {
            return;
        }

        $requiredIds = array_map(fn ($v) => $v->id, $required);
        $accepted = array_map('intval', (array) ($input['accepted_policy_version_ids'] ?? []));
        $missing = array_diff($requiredIds, $accepted);

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'accepted_policy_version_ids' => __('policies.registration.required_acceptance_missing'),
            ]);
        }
    }
}
