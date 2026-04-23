<?php

namespace App\Domain\Ticketing\Notifications;

use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Fired whenever a ticket's QR nonce rotates post-issuance (addUser,
 * removeUser, updateManager, explicit rotate). Sent to the ticket owner,
 * all currently-assigned users, and any previously-attached user or
 * manager that should know their printed copy is now invalid.
 *
 * Channels are always mail + database; security-relevant notifications
 * are not user-suppressible.
 *
 * @see docs/mil-std-498/SRS.md TKT-F-025
 */
class TicketTokenRotatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly string $reason,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $eventName = $this->ticket->event?->name ?? 'your event';

        $message = (new MailMessage)
            ->subject(__('ticketing.notifications.token_rotated.subject'))
            ->line(__('ticketing.notifications.token_rotated.body', ['id' => $this->ticket->id, 'event' => $eventName]))
            ->line($this->reasonLine())
            ->line(__('ticketing.notifications.token_rotated.copies_invalid'));

        if ($this->ticket->id) {
            $message->action(__('ticketing.notifications.token_rotated.action'), route('tickets.show', $this->ticket));
        }

        return $message
            ->line(__('ticketing.notifications.token_rotated.instructions'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'event_name' => $this->ticket->event?->name,
            'reason' => $this->reason,
        ];
    }

    private function reasonLine(): string
    {
        return match ($this->reason) {
            'user-added' => __('ticketing.notifications.token_rotated.reason_user_added'),
            'user-removed' => __('ticketing.notifications.token_rotated.reason_user_removed'),
            'manager-changed' => __('ticketing.notifications.token_rotated.reason_manager_changed'),
            'user-requested' => __('ticketing.notifications.token_rotated.reason_user_requested'),
            default => __('ticketing.notifications.token_rotated.reason_default'),
        };
    }
}
