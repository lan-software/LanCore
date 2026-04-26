<?php

namespace App\Http\Requests;

use App\Concerns\ProfileValidationRules;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @see docs/mil-std-498/SRS.md USR-F-022
 */
class OnboardingUsernameRequest extends FormRequest
{
    use ProfileValidationRules;

    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->username === null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'username' => $this->usernameRules(),
        ];
    }
}
