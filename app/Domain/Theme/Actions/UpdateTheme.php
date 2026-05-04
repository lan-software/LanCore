<?php

namespace App\Domain\Theme\Actions;

use App\Domain\Theme\Models\Theme;
use Illuminate\Support\Facades\DB;

/**
 * @see docs/mil-std-498/SSS.md CAP-THM-001
 * @see docs/mil-std-498/SRS.md THM-F-001
 */
class UpdateTheme
{
    /**
     * @param  array{name?: string, description?: string|null, light_config?: array<string, string>|null, dark_config?: array<string, string>|null}  $attributes
     */
    public function execute(Theme $theme, array $attributes): Theme
    {
        return DB::transaction(function () use ($theme, $attributes): Theme {
            $theme->fill([
                'name' => $attributes['name'] ?? $theme->name,
                'description' => array_key_exists('description', $attributes) ? $attributes['description'] : $theme->description,
                'light_config' => array_key_exists('light_config', $attributes) ? $attributes['light_config'] : $theme->light_config,
                'dark_config' => array_key_exists('dark_config', $attributes) ? $attributes['dark_config'] : $theme->dark_config,
            ])->save();

            return $theme;
        });
    }
}
