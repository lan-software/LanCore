<?php

namespace App\Domain\Venue\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVenueRequest extends FormRequest
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
            'street' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'zip_code' => ['required', 'string', 'max:20'],
            'state' => ['nullable', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:255'],
            'existing_images' => ['sometimes', 'array'],
            'existing_images.*.id' => ['required', 'integer', 'exists:venue_images,id'],
            'existing_images.*.alt_text' => ['nullable', 'string', 'max:255'],
            'new_images' => ['sometimes', 'array'],
            'new_images.*.file' => ['required', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'],
            'new_images.*.alt_text' => ['nullable', 'string', 'max:255'],
        ];
    }
}
