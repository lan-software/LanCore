<?php

namespace App\Domain\OrgaTeam\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrgaTeamRequest extends FormRequest
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
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('orga_teams', 'slug')],
            'description' => ['nullable', 'string'],
            'organizer_user_id' => ['required', 'string', 'ulid', 'exists:users,id'],
            'deputy_user_ids' => ['sometimes', 'array'],
            'deputy_user_ids.*' => ['string', 'ulid', 'distinct', 'exists:users,id', 'different:organizer_user_id'],
        ];
    }
}
