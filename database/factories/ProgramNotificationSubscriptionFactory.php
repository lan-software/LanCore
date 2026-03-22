<?php

namespace Database\Factories;

use App\Domain\Notification\Models\ProgramNotificationSubscription;
use App\Domain\Program\Models\Program;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProgramNotificationSubscription>
 */
class ProgramNotificationSubscriptionFactory extends Factory
{
    protected $model = ProgramNotificationSubscription::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'program_id' => Program::factory(),
        ];
    }
}
