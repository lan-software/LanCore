<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Domain\Policy\Models\Policy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
        $this->configureDemoMode();
    }

    /**
     * Disable the Fortify registration feature when the application runs in demo mode.
     */
    private function configureDemoMode(): void
    {
        if (! config('app.demo')) {
            return;
        }

        config([
            'fortify.features' => array_values(array_filter(
                (array) config('fortify.features'),
                fn ($feature): bool => $feature !== Features::registration(),
            )),
        ]);
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render('auth/Login', [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'canRegister' => Features::enabled(Features::registration()),
            'status' => $request->session()->get('status'),
        ]));

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/ForgotPassword', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::verifyEmailView(fn (Request $request) => Inertia::render('auth/VerifyEmail', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::registerView(fn () => Inertia::render('auth/Register', [
            'requiredPolicies' => $this->resolveRequiredRegistrationPolicies(),
        ]));

        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/TwoFactorChallenge'));

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/ConfirmPassword'));
    }

    /**
     * Build the policy props consumed by the Register page so users see one
     * checkbox per currently-required, currently-effective policy version.
     *
     * @return array<int, array<string, mixed>>
     *
     * @see docs/mil-std-498/SSS.md CAP-POL-004
     */
    private function resolveRequiredRegistrationPolicies(): array
    {
        $locale = (string) app()->getLocale();

        return Policy::query()
            ->active()
            ->requiredForRegistration()
            ->with('type')
            ->orderBy('sort_order')
            ->get()
            ->map(function (Policy $policy) use ($locale): array {
                $version = $policy->currentVersionFor($locale);

                return [
                    'id' => $policy->id,
                    'key' => $policy->key,
                    'name' => $policy->name,
                    'description' => $policy->description,
                    'type' => $policy->type ? ['key' => $policy->type->key, 'label' => $policy->type->label] : null,
                    'current_version' => $version ? [
                        'id' => $version->id,
                        'version_number' => $version->version_number,
                        'locale' => $version->locale,
                        'effective_at' => $version->effective_at,
                    ] : null,
                ];
            })
            ->filter(fn (array $p) => $p['current_version'] !== null)
            ->values()
            ->all();
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
