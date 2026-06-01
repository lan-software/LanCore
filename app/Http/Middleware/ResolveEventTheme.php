<?php

namespace App\Http\Middleware;

use App\Domain\Event\Models\Event;
use App\Domain\Theme\Models\Theme;
use App\Models\OrganizationSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the active palette Theme for the request and shares it with
 * Inertia (and the Blade root template) so the SSR HTML and the
 * client-side ThemeProvider receive a consistent view of the palette.
 *
 * Resolution order: per-event theme assignment beats the organization
 * default; a missing or unresolved theme yields null and the platform's
 * default light/dark palette stays in effect.
 *
 * Runs after HandleAppearance and before HandleInertiaRequests in the web group.
 *
 * @see docs/mil-std-498/SSS.md CAP-THM-003, CAP-THM-004
 * @see docs/mil-std-498/SRS.md THM-F-005
 * @see docs/mil-std-498/SDD.md §5.11
 */
class ResolveEventTheme
{
    /**
     * Sentinel cached when no organization default theme is configured.
     *
     * Cache::remember() treats a null return as "not cached" and re-runs the
     * callback (hitting the database) on every request. Storing a non-null
     * marker lets the "no default" answer be cached like any other, avoiding a
     * perpetual cache miss + query on routes without an event-scoped theme.
     */
    private const NO_DEFAULT = '__none__';

    public function handle(Request $request, Closure $next): Response
    {
        View::share('activeTheme', $this->resolve($request));

        return $next($request);
    }

    /**
     * @return array{id: string, name: string, lightConfig: array<string, string>, darkConfig: array<string, string>, source: 'event'|'organization'}|null
     */
    private function resolve(Request $request): ?array
    {
        $event = $request->route('event');

        if ($event instanceof Event && $event->theme_id !== null) {
            $event->loadMissing('theme');

            if ($event->theme !== null) {
                return $this->payload($event->theme, 'event');
            }
        }

        $defaultId = Cache::remember(
            'inertia.activeTheme.default_id',
            3600,
            fn () => OrganizationSetting::get('default_theme_id') ?? self::NO_DEFAULT,
        );

        if ($defaultId === self::NO_DEFAULT || $defaultId === null) {
            return null;
        }

        $theme = Theme::find((string) $defaultId);

        return $theme === null ? null : $this->payload($theme, 'organization');
    }

    /**
     * @return array{id: string, name: string, lightConfig: array<string, string>, darkConfig: array<string, string>, source: 'event'|'organization'}
     */
    private function payload(Theme $theme, string $source): array
    {
        return [
            'id' => $theme->id,
            'name' => $theme->name,
            'lightConfig' => $theme->light_config ?? [],
            'darkConfig' => $theme->dark_config ?? [],
            'source' => $source,
        ];
    }
}
