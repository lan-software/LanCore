<?php

namespace App\Domain\Event\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'banner_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'],
            'remove_banner_image' => ['sometimes', 'boolean'],
            'seat_capacity' => ['nullable', 'integer', 'min:1'],
            'venue_id' => ['nullable', 'integer', 'exists:venues,id'],
        ];
    }
}
