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
            'data' => ['sometimes', 'nullable', 'json'],
            // Admin acknowledges that existing occupied seats will be released.
            // Defaults to false so the action runs in diff-only mode on first POST.
            'confirm_invalidations' => ['sometimes', 'boolean'],
        ];
    }
}
