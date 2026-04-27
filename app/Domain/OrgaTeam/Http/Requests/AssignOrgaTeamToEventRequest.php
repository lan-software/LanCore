<?php

namespace App\Domain\OrgaTeam\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignOrgaTeamToEventRequest extends FormRequest
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
            'orga_team_id' => ['nullable', 'integer', 'exists:orga_teams,id'],
        ];
    }
}
