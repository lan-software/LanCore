<?php

namespace App\Domain\Shop\Notifications;

use App\Domain\Shop\Enums\Currency;
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
        $currency = Currency::tryFrom((string) $order->currency) ?? Currency::EUR;

        $message = (new MailMessage)
            ->subject(__('shop.notifications.order_confirmation.subject', ['id' => $order->id]))
            ->greeting(__('shop.notifications.order_confirmation.greeting', ['name' => $notifiable->name]))
            ->line(__('shop.notifications.order_confirmation.intro'));

        if ($order->event) {
            $message->line(__('shop.notifications.order_confirmation.event_line', ['name' => $order->event->name]));
        }

        $message->line('---');

        foreach ($order->orderLines as $line) {
            $message->line("{$line->quantity}x {$line->description} — ".$currency->formatCents($line->total_price));
        }

        $message->line('---');

        $message->line(__('shop.notifications.order_confirmation.subtotal_line', ['amount' => $currency->formatCents($order->subtotal)]));

        if ($order->discount > 0) {
            if ($order->voucher?->code) {
                $message->line(__('shop.notifications.order_confirmation.discount_voucher_line', [
                    'code' => $order->voucher->code,
                    'amount' => $currency->formatCents($order->discount),
                ]));
            } else {
                $message->line(__('shop.notifications.order_confirmation.discount_line', ['amount' => $currency->formatCents($order->discount)]));
            }
        }

        $message->line(__('shop.notifications.order_confirmation.total_line', ['amount' => $currency->formatCents($order->total)]));
        $message->line(__('shop.notifications.order_confirmation.payment_line', ['method' => $order->payment_method->label()]));

        $message->action(__('shop.notifications.order_confirmation.action'), url('/tickets'));

        return $message;
    }
}
