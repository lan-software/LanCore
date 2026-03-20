<?php

namespace App\Domain\Program\Http\Requests;

use App\Domain\Program\Enums\ProgramVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProgramRequest extends FormRequest
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
            'visibility' => ['required', 'string', Rule::enum(ProgramVisibility::class)],
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'is_primary' => ['sometimes', 'boolean'],
            'time_slots' => ['sometimes', 'array'],
            'time_slots.*.name' => ['required', 'string', 'max:255'],
            'time_slots.*.description' => ['nullable', 'string'],
            'time_slots.*.starts_at' => ['required', 'date'],
            'time_slots.*.visibility' => ['required', 'string', Rule::enum(ProgramVisibility::class)],
        ];
    }
}
