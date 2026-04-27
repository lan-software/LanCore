<?php

namespace App\Domain\OrgaTeam\Http\Requests;

use App\Domain\OrgaTeam\Enums\SubTeamRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncOrgaSubTeamMembersRequest extends FormRequest
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
            'memberships' => ['present', 'array'],
            'memberships.*.user_id' => ['required', 'integer', 'exists:users,id'],
            'memberships.*.role' => ['required', Rule::enum(SubTeamRole::class)],
        ];
    }
}
