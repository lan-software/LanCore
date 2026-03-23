<?php

namespace App\Notifications;

use App\Domain\Shop\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Order $order) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order->load(['orderLines', 'event', 'voucher']);

        $message = (new MailMessage)
            ->subject('Order Confirmation #'.$order->id)
            ->greeting("Hello {$notifiable->name},")
            ->line('Thank you for your purchase! Here is your order summary:');

        if ($order->event) {
            $message->line("**Event:** {$order->event->name}");
        }

        $message->line('---');

        foreach ($order->orderLines as $line) {
            $price = number_format($line->total_price / 100, 2, '.', ',');
            $message->line("{$line->quantity}x {$line->description} — {$price} €");
        }

        $message->line('---');

        $subtotal = number_format($order->subtotal / 100, 2, '.', ',');
        $message->line("**Subtotal:** {$subtotal} €");

        if ($order->discount > 0) {
            $discount = number_format($order->discount / 100, 2, '.', ',');
            $voucherLabel = $order->voucher?->code ? " ({$order->voucher->code})" : '';
            $message->line("**Discount{$voucherLabel}:** -{$discount} €");
        }

        $total = number_format($order->total / 100, 2, '.', ',');
        $message->line("**Total:** {$total} €");
        $message->line("**Payment:** {$order->payment_method->label()}");

        $message->action('View My Tickets', url('/tickets'));

        return $message;
    }
}
