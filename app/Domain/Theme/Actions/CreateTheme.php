<?php

namespace App\Domain\Theme\Actions;

use App\Domain\Theme\Models\Theme;
use Illuminate\Support\Facades\DB;

/**
 * @see docs/mil-std-498/SSS.md CAP-THM-001
 * @see docs/mil-std-498/SRS.md THM-F-001
 */
class CreateTheme
{
    /**
     * @param  array{name: string, description?: string|null, light_config?: array<string, string>|null, dark_config?: array<string, string>|null}  $attributes
     */
    public function execute(array $attributes): Theme
    {
        return DB::transaction(fn (): Theme => Theme::create([
            'name' => $attributes['name'],
            'description' => $attributes['description'] ?? null,
            'light_config' => $attributes['light_config'] ?? null,
            'dark_config' => $attributes['dark_config'] ?? null,
        ]));
    }
}
