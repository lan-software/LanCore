<?php

namespace App\Http\Requests\Users;

use App\Enums\RoleName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserBulkRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'role' => ['required', 'string', Rule::in(array_column(RoleName::cases(), 'value'))],
        ];
    }
}
