<?php

namespace App\Domain\Policy\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'policy_type_id' => ['required', 'integer', 'exists:policy_types,id'],
            'key' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9_-]+$/', 'unique:policies,key'],
            'name' => ['required', 'string', 'max:128'],
            'description' => ['nullable', 'string'],
            'is_required_for_registration' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
