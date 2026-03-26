<?php

namespace App\Domain\Integration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IntegrationAppIndexRequest extends FormRequest
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
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sort' => ['sometimes', 'nullable', 'string', Rule::in(['name', 'slug', 'is_active', 'created_at'])],
            'direction' => ['sometimes', 'nullable', 'string', Rule::in(['asc', 'desc'])],
            'per_page' => ['sometimes', 'nullable', 'integer', Rule::in([10, 20, 50, 100])],
        ];
    }
}
