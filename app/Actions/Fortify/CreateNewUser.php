<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Domain\Policy\Actions\RecordPolicyAcceptance;
use App\Domain\Policy\Enums\PolicyAcceptanceSource;
use App\Domain\Policy\Models\Policy;
use App\Domain\Policy\Models\PolicyVersion;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user. The user must accept every
     * currently-required platform policy as part of registration.
     *
     * @param  array<string, mixed>  $input
     *
     * @see docs/mil-std-498/SSS.md CAP-POL-004
     * @see docs/mil-std-498/SRS.md POL-F-010
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'accepted_policy_version_ids' => ['sometimes', 'array'],
            'accepted_policy_version_ids.*' => ['integer', 'exists:policy_versions,id'],
        ])->validate();

        $requiredVersions = $this->resolveRequiredVersions();
        $this->ensureRequiredPoliciesAccepted($requiredVersions, $input);

        $user = User::create([
            'name' => $input['name'],
            'username' => $input['username'],
            'email' => $input['email'],
            'password' => $input['password'],
        ]);

        $recordAcceptance = app(RecordPolicyAcceptance::class);
        foreach ($requiredVersions as $version) {
            $recordAcceptance->execute(
                $user,
                $version,
                PolicyAcceptanceSource::Registration,
                request(),
            );
        }

        return $user;
    }

    /**
     * @return array<int, PolicyVersion>
     */
    private function resolveRequiredVersions(): array
    {
        return Policy::query()
            ->active()
            ->requiredForRegistration()
            ->with('currentVersion')
            ->get()
            ->map(fn (Policy $policy) => $policy->currentVersion)
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, PolicyVersion>  $requiredVersions
     * @param  array<string, mixed>  $input
     */
    private function ensureRequiredPoliciesAccepted(array $requiredVersions, array $input): void
    {
        if ($requiredVersions === []) {
            return;
        }

        $required = array_map(fn (PolicyVersion $v) => $v->id, $requiredVersions);
        $accepted = array_map('intval', (array) ($input['accepted_policy_version_ids'] ?? []));
        $missing = array_diff($required, $accepted);

        if (! empty($missing)) {
            throw ValidationException::withMessages([
                'accepted_policy_version_ids' => __('policies.registration.required_acceptance_missing'),
            ]);
        }
    }
}
