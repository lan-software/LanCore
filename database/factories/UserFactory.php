<?php

namespace Database\Factories;

use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => $this->generateUsername(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'avatar_source' => 'default',
            'profile_visibility' => 'logged_in',
        ];
    }

    /**
     * Indicate that the user has not yet picked a username (transitional
     * state for users created before USR-F-022 shipped).
     */
    public function withoutUsername(): static
    {
        return $this->state(fn (array $attributes): array => [
            'username' => null,
        ]);
    }

    private function generateUsername(): string
    {
        $candidate = preg_replace('/[^A-Za-z0-9]+/', '_', fake()->userName());
        $candidate = trim((string) $candidate, '_-');

        if (strlen($candidate) < 3) {
            $candidate = 'player_'.Str::random(4);
        }

        if (strlen($candidate) > 32) {
            $candidate = substr($candidate, 0, 32);
        }

        return $candidate.'_'.Str::random(4);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    /**
     * Indicate that the user has a complete profile (address + contact).
     */
    public function withCompleteProfile(): static
    {
        return $this->state(fn (array $attributes): array => [
            'phone' => fake()->phoneNumber(),
            'street' => fake()->streetAddress(),
            'city' => fake()->city(),
            'zip_code' => fake()->postcode(),
            'country' => fake()->countryCode(),
        ]);
    }

    /**
     * Attach a role after user creation.
     */
    public function withRole(RoleName $role): static
    {
        return $this->afterCreating(function (User $user) use ($role): void {
            $roleModel = Role::where('name', $role->value)->first();

            if ($roleModel) {
                $user->roles()->attach($roleModel);
            }
        });
    }
}
