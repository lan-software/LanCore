<?php

namespace App\Domain\Theme\Http\Requests;

use App\Domain\Theme\Support\PaletteVariables;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Reject `light_config` / `dark_config` keys that are not in the curated
 * PaletteVariables allowlist. The regex check is a defense-in-depth guard
 * against CSS-injection breakout from the server-rendered `<style>` block.
 *
 * @see docs/mil-std-498/SRS.md THM-F-002
 */
class ThemeConfigKeysRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            return;
        }

        $allowed = PaletteVariables::allowedKeys();

        foreach (array_keys($value) as $key) {
            if (! is_string($key) || preg_match('/^--[a-z][a-z0-9-]*$/', $key) !== 1) {
                $fail("Invalid CSS variable key: {$key}");

                continue;
            }

            if (! in_array($key, $allowed, true)) {
                $fail("CSS variable not in palette allowlist: {$key}");
            }
        }
    }
}
