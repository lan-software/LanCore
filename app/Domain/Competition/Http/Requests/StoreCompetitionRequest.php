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
     * @see UpdateCompetitionRequest::prepareForValidation
     */
    protected function prepareForValidation(): void
    {
        if (! is_array($this->input('referee_ids'))) {
            return;
        }

        $this->merge([
            'referee_ids' => array_values(array_filter(
                $this->input('referee_ids'),
                fn ($id): bool => is_string($id) && $id !== '',
            )),
        ]);
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
            'event_id' => ['nullable', 'string', 'ulid', 'exists:events,id'],
            'game_id' => ['nullable', 'string', 'ulid', 'exists:games,id'],
            'game_mode_id' => ['nullable', 'string', 'ulid', 'exists:game_modes,id'],
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
            'match_length_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp,svg', 'max:2048'],
            'banner' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'],
            'rules_markdown' => ['nullable', 'string', 'max:20000'],
            'referee_ids' => ['nullable', 'array'],
            'referee_ids.*' => ['string', 'ulid', 'exists:users,id'],
        ];
    }
}
