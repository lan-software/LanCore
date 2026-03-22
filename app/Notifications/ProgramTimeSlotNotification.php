<?php

namespace App\Notifications;

use App\Domain\Program\Models\TimeSlot;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProgramTimeSlotNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly TimeSlot $timeSlot) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function shouldSend(object $notifiable, string $channel): bool
    {
        if ($channel === 'database') {
            return true;
        }

        $preferences = $notifiable->notificationPreference;

        if (! $preferences) {
            return true;
        }

        return match ($channel) {
            'mail' => $preferences->mail_on_program_time_slots,
            default => false,
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        $program = $this->timeSlot->program;

        return (new MailMessage)
            ->subject('Upcoming: '.$program->name)
            ->line('A program time slot is about to start: '.$program->name)
            ->line('Starts at: '.$this->timeSlot->starts_at->format('H:i'))
            ->action('View program', url('/programs/'.$program->id))
            ->line('You can manage your notification preferences in your account settings.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'time_slot_id' => $this->timeSlot->id,
            'program_id' => $this->timeSlot->program_id,
        ];
    }
}
