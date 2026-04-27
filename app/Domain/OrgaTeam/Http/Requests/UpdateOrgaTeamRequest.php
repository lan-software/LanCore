<?php

namespace App\Domain\OrgaTeam\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrgaTeamRequest extends FormRequest
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
        $teamId = $this->route('orgaTeam')?->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'required', 'string', 'max:255', 'alpha_dash', Rule::unique('orga_teams', 'slug')->ignore($teamId)],
            'description' => ['nullable', 'string'],
            'organizer_user_id' => ['sometimes', 'required', 'integer', 'exists:users,id'],
            'deputy_user_ids' => ['sometimes', 'array'],
            'deputy_user_ids.*' => ['integer', 'distinct', 'exists:users,id', 'different:organizer_user_id'],
        ];
    }
}
