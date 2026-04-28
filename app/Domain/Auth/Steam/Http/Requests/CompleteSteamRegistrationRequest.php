<?php

namespace App\Domain\Auth\Steam\Http\Requests;

use App\Concerns\ProfileValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class CompleteSteamRegistrationRequest extends FormRequest
{
    use ProfileValidationRules;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => $this->nameRules(),
            'username' => $this->usernameRules(),
            'email' => $this->emailRules(),
            'accepted_policy_version_ids' => ['sometimes', 'array'],
            'accepted_policy_version_ids.*' => ['integer', 'exists:policy_versions,id'],
        ];
    }
}
