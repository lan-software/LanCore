<?php

namespace App\Domain\Policy\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePolicyTypeRequest extends FormRequest
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
            'key' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9_-]+$/', 'unique:policy_types,key'],
            'label' => ['required', 'string', 'max:128'],
            'description' => ['nullable', 'string'],
        ];
    }
}
