<?php

namespace App\Domain\Theme\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @see docs/mil-std-498/SRS.md THM-F-002
 */
class StoreThemeRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255', 'unique:themes,name'],
            'description' => ['nullable', 'string', 'max:1000'],
            'light_config' => ['nullable', 'array', new ThemeConfigKeysRule],
            'light_config.*' => ['nullable', 'string', 'max:128', 'not_regex:/[;}<>]/'],
            'dark_config' => ['nullable', 'array', new ThemeConfigKeysRule],
            'dark_config.*' => ['nullable', 'string', 'max:128', 'not_regex:/[;}<>]/'],
        ];
    }
}
