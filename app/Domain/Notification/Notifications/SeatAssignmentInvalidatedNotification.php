<?php

namespace App\Domain\Notification\Notifications;

use App\Domain\Seating\Events\SeatAssignmentInvalidated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to everyone affected by a confirmed seat-plan edit that released
 * existing assignments. Shape mirrors NewsPublishedNotification (mail +
 * database channels by default; push is opt-in via `push_on_seating`).
 *
 * @see docs/mil-std-498/SRS.md SET-F-014
 * @see docs/mil-std-498/SDD.md §5.3c.4
 */
class SeatAssignmentInvalidatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly SeatAssignmentInvalidated $event) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        $preferences = $notifiable->notificationPreference ?? null;

        if ($preferences === null || $preferences->mail_on_seating) {
            $channels[] = 'mail';
        }

        if ($preferences !== null && $preferences->push_on_seating) {
            $channels[] = 'push';
        }

        return $channels;
    }

    public function shouldSend(object $notifiable, string $channel): bool
    {
        return in_array($channel, $this->via($notifiable), true);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $eventName = $this->event->seatPlan->event?->name ?? '';
        $seatLabel = $this->event->previousSeatId;
        $pickerUrl = url('/events/'.$this->event->seatPlan->event_id.'/seats?ticket='.$this->event->ticketId.'&user='.$this->event->userId);

        return (new MailMessage)
            ->subject(__('seating.notifications.invalidated.subject', ['event' => $eventName]))
            ->line(__('seating.notifications.invalidated.body', [
                'seat' => $seatLabel,
                'event' => $eventName,
            ]))
            ->line($this->reasonExplanation())
            ->action(__('seating.notifications.invalidated.action'), $pickerUrl)
            ->line(__('seating.notifications.invalidated.preferences_hint'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->event->ticketId,
            'user_id' => $this->event->userId,
            'event_id' => $this->event->seatPlan->event_id,
            'seat_plan_id' => $this->event->seatPlan->id,
            'previous_seat_id' => $this->event->previousSeatId,
            'previous_block_id' => $this->event->previousBlockId,
            'reason' => $this->event->reason,
        ];
    }

    private function reasonExplanation(): string
    {
        return match ($this->event->reason) {
            'category_mismatch' => __('seating.notifications.invalidated.reason_category_mismatch'),
            default => __('seating.notifications.invalidated.reason_seat_removed'),
        };
    }
}
