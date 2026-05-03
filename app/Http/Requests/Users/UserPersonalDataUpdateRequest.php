<?php

namespace App\Http\Requests\Users;

use App\Domain\Profile\Enums\ProfileVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserPersonalDataUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['nullable', 'string', 'max:50'],
            'street' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'size:2'],
            'short_bio' => ['nullable', 'string', 'max:255'],
            'profile_description' => ['nullable', 'string', 'max:5000'],
            'profile_emoji' => ['nullable', 'string', 'max:8'],
            'profile_visibility' => [
                'nullable',
                'string',
                Rule::in(array_column(ProfileVisibility::cases(), 'value')),
            ],
            'is_ticket_discoverable' => ['sometimes', 'boolean'],
            'is_seat_visible_publicly' => ['sometimes', 'boolean'],
        ];
    }
}
