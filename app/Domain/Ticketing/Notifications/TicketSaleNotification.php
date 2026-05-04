<?php

namespace App\Domain\Ticketing\Notifications;

use App\Domain\Ticketing\Enums\TicketSalePhase;
use App\Domain\Ticketing\Models\TicketType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * @see docs/mil-std-498/SDD.md §5.13
 * @see docs/mil-std-498/SRS.md NTF-F-008, NTF-F-009, NTF-F-010, NTF-F-011, NTF-F-012
 */
class TicketSaleNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly TicketType $ticketType,
        public readonly TicketSalePhase $phase,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'webpush'];
    }

    public function shouldSend(object $notifiable, string $channel): bool
    {
        if ($channel === 'database') {
            return true;
        }

        $preferences = $notifiable->notificationPreference ?? null;

        return match ($channel) {
            'mail' => $preferences?->mail_on_ticket_sale ?? true,
            'webpush' => ($preferences?->push_on_ticket_sale ?? false)
                && $notifiable->pushSubscriptions()->exists(),
            default => false,
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        $event = $this->ticketType->event;
        $shopUrl = url(route('shop.index', absolute: false));

        if ($this->phase === TicketSalePhase::Release) {
            return (new MailMessage)
                ->subject("Tickets on sale: {$this->ticketType->name}")
                ->line("Tickets for **{$this->ticketType->name}** ({$event->name}) are now on sale.")
                ->action('Get your ticket', $shopUrl)
                ->line('You can manage your notification preferences in your account settings.');
        }

        return (new MailMessage)
            ->subject("Last chance: {$this->ticketType->name}")
            ->line("Sales for **{$this->ticketType->name}** ({$event->name}) are ending soon.")
            ->action('Get your ticket before it closes', $shopUrl)
            ->line('You can manage your notification preferences in your account settings.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $event = $this->ticketType->event;

        return [
            'phase' => $this->phase->value,
            'ticket_type_id' => $this->ticketType->id,
            'ticket_type_name' => $this->ticketType->name,
            'event_id' => $event?->id,
            'event_name' => $event?->name,
            'price_cents' => $this->ticketType->price,
            'purchase_from' => $this->ticketType->purchase_from?->toIso8601String(),
            'purchase_until' => $this->ticketType->purchase_until?->toIso8601String(),
            'shop_url' => url(route('shop.index', absolute: false)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toWebPush(object $notifiable): array
    {
        $event = $this->ticketType->event;
        $shopUrl = url(route('shop.index', absolute: false));

        if ($this->phase === TicketSalePhase::Release) {
            return [
                'title' => "Tickets on sale: {$this->ticketType->name}",
                'body' => "Tickets for {$event?->name} are now available.",
                'url' => $shopUrl,
            ];
        }

        return [
            'title' => "Last chance: {$this->ticketType->name}",
            'body' => "Sales for {$event?->name} are ending soon.",
            'url' => $shopUrl,
        ];
    }
}
