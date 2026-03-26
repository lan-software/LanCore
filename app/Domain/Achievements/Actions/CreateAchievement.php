<?php

namespace App\Domain\Achievements\Actions;

use App\Domain\Achievements\Models\Achievement;

class CreateAchievement
{
    /**
     * @param  array{name: string, description?: string|null, notification_text?: string|null, color: string, icon: string, is_active?: bool}  $attributes
     * @param  array<string>  $eventClasses
     */
    public function execute(array $attributes, array $eventClasses = []): Achievement
    {
        $achievement = Achievement::create($attributes);

        foreach ($eventClasses as $eventClass) {
            $achievement->achievementEvents()->create([
                'event_class' => $eventClass,
            ]);
        }

        return $achievement;
    }
}
