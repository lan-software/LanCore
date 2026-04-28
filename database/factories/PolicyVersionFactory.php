<?php

namespace Database\Factories;

use App\Domain\Policy\Models\Policy;
use App\Domain\Policy\Models\PolicyVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PolicyVersion>
 */
class PolicyVersionFactory extends Factory
{
    protected $model = PolicyVersion::class;

    public function definition(): array
    {
        $now = now();

        return [
            'policy_id' => Policy::factory(),
            'version_number' => 1,
            'locale' => config('app.locale'),
            'content' => fake()->paragraphs(3, true),
            'public_statement' => null,
            'is_non_editorial_change' => false,
            'pdf_path' => null,
            'effective_at' => $now,
            'published_at' => $now,
            'published_by_user_id' => User::factory(),
        ];
    }

    public function nonEditorial(?string $publicStatement = null): static
    {
        return $this->state(fn () => [
            'is_non_editorial_change' => true,
            'public_statement' => $publicStatement ?? fake()->paragraph(),
        ]);
    }
}
