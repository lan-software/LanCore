<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @see docs/mil-std-498/SRS.md I18N-F-003
 */
class SetLocale
{
    /**
     * Available UI locales. Must match resources/js/locales/*.json filenames
     * and the Rule::in() set in ProfileValidationRules::localeRules().
     *
     * @var array<int, string>
     */
    public const AVAILABLE = ['en', 'de', 'fr', 'es', 'sv', 'uk', 'ko', 'tlh', 'nds', 'sxu'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);

        if (in_array($locale, self::AVAILABLE, true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        $user = $request->user();

        if ($user && ! empty($user->locale)) {
            return $user->locale;
        }

        $preferred = $request->getPreferredLanguage(self::AVAILABLE);

        if ($preferred) {
            return $preferred;
        }

        return config('app.fallback_locale', 'en');
    }
}
