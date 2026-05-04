<?php

namespace App\Domain\Newsletter\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @see docs/mil-std-498/SRS.md NLT-F-001
 */
class StoreNewsletterListRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', 'in:public,private'],
            'optin' => ['required', 'in:single,double'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:64'],
            'is_user_selectable' => ['boolean'],
            'is_default_public' => ['boolean'],
        ];
    }
}
