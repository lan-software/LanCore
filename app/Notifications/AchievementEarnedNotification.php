<?php

namespace App\Notifications;

use App\Domain\Achievements\Models\Achievement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AchievementEarnedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Achievement $achievement) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'achievement_id' => $this->achievement->id,
            'name' => $this->achievement->name,
            'description' => $this->achievement->notification_text ?? $this->achievement->description,
            'color' => $this->achievement->color,
            'icon' => $this->achievement->icon,
        ];
    }
}
