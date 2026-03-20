<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sort' => ['sometimes', 'nullable', 'string', Rule::in(['name', 'email', 'created_at'])],
            'direction' => ['sometimes', 'nullable', 'string', Rule::in(['asc', 'desc'])],
            'role' => ['sometimes', 'nullable', 'string', Rule::in(['user', 'admin', 'superadmin'])],
            'per_page' => ['sometimes', 'nullable', 'integer', Rule::in([10, 20, 50, 100])],
        ];
    }
}
