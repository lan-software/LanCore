<?php

namespace Database\Factories;

use App\Domain\Policy\Models\Policy;
use App\Domain\Policy\Models\PolicyLocaleDraft;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PolicyLocaleDraft>
 */
class PolicyLocaleDraftFactory extends Factory
{
    protected $model = PolicyLocaleDraft::class;

    public function definition(): array
    {
        return [
            'policy_id' => Policy::factory(),
            'locale' => config('app.locale'),
            'content' => fake()->paragraphs(3, true),
            'updated_by_user_id' => null,
        ];
    }
}
