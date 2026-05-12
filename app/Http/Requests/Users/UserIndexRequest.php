<?php

namespace App\Http\Requests\Users;

use App\Domain\Auth\Steam\Enums\SteamLinkStatus;
use App\Enums\RoleName;
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
            'sort' => ['sometimes', 'nullable', 'string', Rule::in(['name', 'email', 'created_at', 'presence'])],
            'direction' => ['sometimes', 'nullable', 'string', Rule::in(['asc', 'desc'])],
            'role' => ['sometimes', 'nullable', 'string', Rule::in(array_column(RoleName::cases(), 'value'))],
            'steam_status' => ['sometimes', 'nullable', 'string', Rule::in(array_column(SteamLinkStatus::cases(), 'value'))],
            'per_page' => ['sometimes', 'nullable', 'integer', Rule::in([10, 20, 50, 100])],
        ];
    }
}
