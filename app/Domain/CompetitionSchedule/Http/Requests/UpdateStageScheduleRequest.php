<?php

namespace App\Domain\CompetitionSchedule\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @see docs/mil-std-498/SRS.md COMP-SCH-006
 */
class UpdateStageScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('schedule')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'starts_at' => ['nullable', 'date'],
            'estimated_duration_minutes' => ['sometimes', 'integer', 'min:5', 'max:1440'],
            'reserve_buffer_minutes' => ['sometimes', 'integer', 'min:0', 'max:1440'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'reset_to_computed' => ['sometimes', 'boolean'],
        ];
    }
}
