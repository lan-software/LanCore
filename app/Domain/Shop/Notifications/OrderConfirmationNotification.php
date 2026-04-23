<?php

namespace App\Domain\Shop\Notifications;

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
            ->subject(__('shop.notifications.order_confirmation.subject', ['id' => $order->id]))
            ->greeting(__('shop.notifications.order_confirmation.greeting', ['name' => $notifiable->name]))
            ->line(__('shop.notifications.order_confirmation.intro'));

        if ($order->event) {
            $message->line(__('shop.notifications.order_confirmation.event_line', ['name' => $order->event->name]));
        }

        $message->line('---');

        foreach ($order->orderLines as $line) {
            $price = number_format($line->total_price / 100, 2, '.', ',');
            $message->line("{$line->quantity}x {$line->description} — {$price} €");
        }

        $message->line('---');

        $subtotal = number_format($order->subtotal / 100, 2, '.', ',');
        $message->line(__('shop.notifications.order_confirmation.subtotal_line', ['amount' => "{$subtotal} €"]));

        if ($order->discount > 0) {
            $discount = number_format($order->discount / 100, 2, '.', ',');

            if ($order->voucher?->code) {
                $message->line(__('shop.notifications.order_confirmation.discount_voucher_line', [
                    'code' => $order->voucher->code,
                    'amount' => "{$discount} €",
                ]));
            } else {
                $message->line(__('shop.notifications.order_confirmation.discount_line', ['amount' => "{$discount} €"]));
            }
        }

        $total = number_format($order->total / 100, 2, '.', ',');
        $message->line(__('shop.notifications.order_confirmation.total_line', ['amount' => "{$total} €"]));
        $message->line(__('shop.notifications.order_confirmation.payment_line', ['method' => $order->payment_method->label()]));

        $message->action(__('shop.notifications.order_confirmation.action'), url('/tickets'));

        return $message;
    }
}
