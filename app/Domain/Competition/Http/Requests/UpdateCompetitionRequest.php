<?php

namespace App\Domain\Competition\Http\Requests;

use App\Domain\Competition\Enums\CompetitionStatus;
use App\Domain\Competition\Enums\CompetitionType;
use App\Domain\Competition\Enums\ResultSubmissionMode;
use App\Domain\Competition\Enums\StageType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompetitionRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('competitions', 'slug')->ignore($this->route('competition'))],
            'description' => ['nullable', 'string'],
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
            'game_id' => ['nullable', 'integer', 'exists:games,id'],
            'game_mode_id' => ['nullable', 'integer', 'exists:game_modes,id'],
            'type' => ['sometimes', Rule::enum(CompetitionType::class)],
            'stage_type' => ['sometimes', Rule::enum(StageType::class)],
            'status' => ['sometimes', Rule::enum(CompetitionStatus::class)],
            'team_size' => ['nullable', 'integer', 'min:1'],
            'max_teams' => ['nullable', 'integer', 'min:2'],
            'registration_opens_at' => ['nullable', 'date'],
            'registration_closes_at' => ['nullable', 'date'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'settings' => ['nullable', 'array'],
            'settings.result_submission_mode' => ['nullable', Rule::enum(ResultSubmissionMode::class)],
        ];
    }
}
