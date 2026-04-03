<?php

namespace App\Domain\Competition\Http\Requests;

use App\Domain\Competition\Enums\CompetitionType;
use App\Domain\Competition\Enums\ResultSubmissionMode;
use App\Domain\Competition\Enums\StageType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompetitionRequest extends FormRequest
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
            'slug' => ['nullable', 'string', 'max:255', 'unique:competitions,slug'],
            'description' => ['nullable', 'string'],
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
            'game_id' => ['nullable', 'integer', 'exists:games,id'],
            'game_mode_id' => ['nullable', 'integer', 'exists:game_modes,id'],
            'type' => ['required', Rule::enum(CompetitionType::class)],
            'stage_type' => ['required', Rule::enum(StageType::class)],
            'team_size' => ['nullable', 'integer', 'min:1'],
            'max_teams' => ['nullable', 'integer', 'min:2'],
            'registration_opens_at' => ['nullable', 'date'],
            'registration_closes_at' => ['nullable', 'date', 'after:registration_opens_at'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'settings' => ['nullable', 'array'],
            'settings.result_submission_mode' => ['nullable', Rule::enum(ResultSubmissionMode::class)],
        ];
    }
}
