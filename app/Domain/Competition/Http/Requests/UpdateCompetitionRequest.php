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
     * RefereePicker emits a leading empty-string sentinel so the controller
     * can detect "user explicitly cleared the list". Strip those before
     * validation so the `ulid` rule on each element doesn't reject them.
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
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('competitions', 'slug')->ignore($this->route('competition'))],
            'description' => ['nullable', 'string'],
            'event_id' => ['nullable', 'string', 'ulid', 'exists:events,id'],
            'game_id' => ['nullable', 'string', 'ulid', 'exists:games,id'],
            'game_mode_id' => ['nullable', 'string', 'ulid', 'exists:game_modes,id'],
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
            'signup_rules' => ['nullable', 'array'],
            'match_length_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp,svg', 'max:2048'],
            'banner' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'],
            'remove_logo' => ['sometimes', 'boolean'],
            'remove_banner' => ['sometimes', 'boolean'],
            'rules_markdown' => ['nullable', 'string', 'max:20000'],
            'referee_ids' => ['nullable', 'array'],
            'referee_ids.*' => ['string', 'ulid', 'exists:users,id'],
        ];
    }
}
