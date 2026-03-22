<?php

namespace App\Domain\Seating\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSeatPlanRequest extends FormRequest
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
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'data' => ['sometimes', 'nullable', 'json'],
        ];
    }
}
