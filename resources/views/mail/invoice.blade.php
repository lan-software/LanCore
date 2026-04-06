<x-mail::message>
# Invoice {{ $invoiceNumber }}

Your order has been created.

**Order Date:** {{ $order->created_at->format('d M Y, H:i') }}
**Payment Method:** {{ $order->payment_method->label() }}
**Total:** {{ number_format($order->total / 100, 2) }} {{ strtoupper(config('cashier.currency', 'eur')) }}

Please find your invoice attached as a PDF.

<x-mail::button :url="url('/my-orders/' . $order->id)">
View Order
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
