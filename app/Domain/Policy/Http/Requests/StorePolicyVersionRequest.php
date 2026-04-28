<?php

namespace App\Domain\Policy\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePolicyVersionRequest extends FormRequest
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
            'content' => ['required', 'string'],
            'is_non_editorial_change' => ['sometimes', 'boolean'],
            'public_statement' => ['nullable', 'required_if:is_non_editorial_change,true,1', 'string'],
            'locale' => ['nullable', 'string', 'max:10'],
            'effective_at' => ['nullable', 'date'],
        ];
    }
}
