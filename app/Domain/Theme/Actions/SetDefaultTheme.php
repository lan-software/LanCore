<?php

namespace App\Domain\Theme\Actions;

use App\Models\OrganizationSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Persists the site-wide default Theme assignment in `OrganizationSetting`
 * and flushes the cached resolution so the next request picks up the change.
 *
 * @see docs/mil-std-498/SSS.md CAP-THM-001, CAP-THM-004
 * @see docs/mil-std-498/SRS.md THM-F-006
 * @see docs/mil-std-498/SDD.md §5.11
 */
class SetDefaultTheme
{
    public function execute(?int $themeId): void
    {
        OrganizationSetting::set('default_theme_id', $themeId);
        Cache::forget('inertia.activeTheme.default_id');
    }
}
