<?php

namespace App\Domain\Achievements\Actions;

use App\Domain\Achievements\Models\Achievement;

/**
 * @see docs/mil-std-498/SRS.md ACH-F-001
 */
class UpdateAchievement
{
    /**
     * @param  array{name: string, description?: string|null, notification_text?: string|null, color: string, icon: string, is_active?: bool}  $attributes
     * @param  array<string>  $eventClasses
     */
    public function execute(Achievement $achievement, array $attributes, array $eventClasses = []): Achievement
    {
        $achievement->update($attributes);

        $achievement->achievementEvents()->delete();

        foreach ($eventClasses as $eventClass) {
            $achievement->achievementEvents()->create([
                'event_class' => $eventClass,
            ]);
        }

        return $achievement;
    }
}
