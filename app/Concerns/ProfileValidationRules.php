<?php

namespace App\Concerns;

use App\Http\Middleware\SetLocale;
use App\Models\User;
use App\Rules\UniqueUsername;
use Illuminate\Validation\Rule;

trait ProfileValidationRules
{
    /**
     * Get the validation rules used to validate user profiles.
     *
     * @return array<string, array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>>
     */
    protected function profileRules(?int $userId = null): array
    {
        return [
            'name' => $this->nameRules(),
            'username' => $this->usernameRules($userId),
            'email' => $this->emailRules($userId),
            'phone' => ['nullable', 'string', 'max:50'],
            'street' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'size:2'],
            'locale' => $this->localeRules(),
        ];
    }

    /**
     * Get the validation rules used to validate the public-facing username.
     *
     * When $userId is null (signup), username is required. When $userId
     * is set (profile update), `sometimes` lets the caller omit the field
     * entirely while still validating any value sent.
     *
     * @see docs/mil-std-498/SRS.md USR-F-022
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function usernameRules(?int $userId = null): array
    {
        $rules = [
            'string',
            'min:3',
            'max:32',
            'regex:/^[A-Za-z0-9][A-Za-z0-9_-]{1,30}[A-Za-z0-9]$/',
            new UniqueUsername($userId),
        ];

        array_unshift($rules, $userId === null ? 'required' : 'sometimes');

        return $rules;
    }

    /**
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function localeRules(): array
    {
        return ['nullable', 'string', Rule::in(SetLocale::AVAILABLE)];
    }

    /**
     * Get the validation rules used to validate user names.
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function nameRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    /**
     * Get the validation rules used to validate user emails.
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function emailRules(?int $userId = null): array
    {
        return [
            'required',
            'string',
            'email',
            'max:255',
            $userId === null
                ? Rule::unique(User::class)
                : Rule::unique(User::class)->ignore($userId),
        ];
    }
}
