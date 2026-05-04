<?php

namespace App\Domain\Theme\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @see docs/mil-std-498/SRS.md THM-F-002
 */
class UpdateThemeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string|ValidationRule>>
     */
    public function rules(): array
    {
        $themeId = $this->route('theme')?->id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('themes', 'name')->ignore($themeId)],
            'description' => ['nullable', 'string', 'max:1000'],
            'light_config' => ['nullable', 'array', new ThemeConfigKeysRule],
            'light_config.*' => ['nullable', 'string', 'max:128', 'not_regex:/[;}<>]/'],
            'dark_config' => ['nullable', 'array', new ThemeConfigKeysRule],
            'dark_config.*' => ['nullable', 'string', 'max:128', 'not_regex:/[;}<>]/'],
        ];
    }
}
