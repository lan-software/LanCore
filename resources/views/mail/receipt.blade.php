<x-mail::message>
# Payment Receipt — {{ $invoiceNumber }}

Your payment has been received. Thank you!

**Payment Date:** {{ $order->paid_at->format('d M Y, H:i') }}
**Payment Method:** {{ $order->payment_method->label() }}
**Total Paid:** {{ number_format($order->total / 100, 2) }} {{ strtoupper($order->currency ?: 'eur') }}

Please find your receipt attached as a PDF.

<x-mail::button :url="url('/my-orders/' . $order->id)">
View Order
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
