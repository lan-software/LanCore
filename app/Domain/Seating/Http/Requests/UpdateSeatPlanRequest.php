<?php

namespace App\Domain\Seating\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSeatPlanRequest extends FormRequest
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
            'background_image_url' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'data' => ['sometimes', 'nullable'],
            'confirm_invalidations' => ['sometimes', 'boolean'],
        ];
    }
}
