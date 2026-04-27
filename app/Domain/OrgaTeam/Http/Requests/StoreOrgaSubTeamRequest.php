<?php

namespace App\Domain\OrgaTeam\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrgaSubTeamRequest extends FormRequest
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
            'description' => ['nullable', 'string'],
            'emoji' => ['nullable', 'string', 'max:8'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'leader_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
