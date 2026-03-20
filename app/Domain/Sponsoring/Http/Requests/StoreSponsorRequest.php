<?php

namespace App\Domain\Sponsoring\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSponsorRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'link' => ['nullable', 'url', 'max:2048'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp,svg', 'max:2048'],
            'sponsor_level_id' => ['nullable', 'integer', 'exists:sponsor_levels,id'],
            'event_ids' => ['sometimes', 'array'],
            'event_ids.*' => ['integer', 'exists:events,id'],
        ];
    }
}
