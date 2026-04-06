<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; line-height: 1.5; }
        .container { padding: 40px; }
        .org-name { font-size: 18px; font-weight: bold; margin-bottom: 4px; }
        .org-detail { font-size: 9px; color: #666; }
        .document-title { font-size: 22px; font-weight: bold; color: #333; margin-bottom: 8px; }
        .document-number { font-size: 12px; color: #666; }
        .address-label { font-size: 9px; color: #999; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .meta-table { border-collapse: collapse; }
        .meta-table td { padding: 4px 0; font-size: 10px; }
        .meta-table .label { color: #666; width: 140px; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.items thead th { background: #f5f5f5; padding: 8px 10px; text-align: left; font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; color: #555; border-bottom: 2px solid #ddd; }
        table.items thead th.right { text-align: right; }
        table.items tbody td { padding: 8px 10px; border-bottom: 1px solid #eee; }
        table.items tbody td.right { text-align: right; }
        .totals { margin-left: auto; width: 250px; }
        .totals table { width: 100%; border-collapse: collapse; }
        .totals td { padding: 4px 0; }
        .totals td.right { text-align: right; }
        .totals .total-row { font-weight: bold; font-size: 13px; border-top: 2px solid #333; padding-top: 6px; }
        .paid-stamp { margin-top: 20px; padding: 10px 16px; border: 2px solid #16a34a; color: #16a34a; display: inline-block; font-size: 14px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; border-radius: 4px; }
        .footer { margin-top: 40px; padding-top: 15px; border-top: 1px solid #ddd; font-size: 8px; color: #999; text-align: center; }
        .footer .legal { margin-top: 4px; }
    </style>
</head>
<body>
<div class="container">
    <table style="width:100%; margin-bottom: 30px;">
        <tr>
            <td style="vertical-align: top;">
                @if($org['logo_base64'])
                    <img src="{{ $org['logo_base64'] }}" alt="{{ $org['name'] }}" style="max-height: 50px; max-width: 180px; margin-bottom: 8px;">
                @endif
                <div class="org-name">{{ $org['name'] }}</div>
                @if($org['address_line1'])<div class="org-detail">{{ $org['address_line1'] }}</div>@endif
                @if($org['address_line2'])<div class="org-detail">{{ $org['address_line2'] }}</div>@endif
                @if($org['phone'])<div class="org-detail">Tel: {{ $org['phone'] }}</div>@endif
                @if($org['email'])<div class="org-detail">{{ $org['email'] }}</div>@endif
            </td>
            <td style="text-align: right; vertical-align: top;">
                <div class="document-title">RECEIPT</div>
                <div class="document-number">{{ $order->invoice_number }}</div>
            </td>
        </tr>
    </table>

    <table style="width: 100%; margin-bottom: 30px;">
        <tr>
            <td style="vertical-align: top;">
                <div class="address-label">Received From</div>
                <div><strong>{{ $order->user->name }}</strong></div>
                <div>{{ $order->user->email }}</div>
            </td>
            <td style="text-align: right; vertical-align: top;">
                <table class="meta-table" style="margin-left: auto; width: 250px;">
                    <tr><td class="label">Receipt Date</td><td style="text-align: right;">{{ $order->paid_at?->format('d M Y') ?? $order->created_at->format('d M Y') }}</td></tr>
                    <tr><td class="label">Receipt Time</td><td style="text-align: right;">{{ $order->paid_at?->format('H:i') ?? $order->created_at->format('H:i') }}</td></tr>
                    <tr><td class="label">Payment Method</td><td style="text-align: right;">{{ $order->payment_method->label() }}</td></tr>
                    @if($order->event)<tr><td class="label">Event</td><td style="text-align: right;">{{ $order->event->name }}</td></tr>@endif
                    @if($order->confirmedBy)<tr><td class="label">Payment received by</td><td style="text-align: right;">{{ $order->confirmedBy->name }}</td></tr>@endif
                    @if($org['tax_id'])<tr><td class="label">Tax ID</td><td style="text-align: right;">{{ $org['tax_id'] }}</td></tr>@endif
                </table>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Description</th>
                <th class="right">Qty</th>
                <th class="right">Unit Price</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->orderLines as $line)
            <tr>
                <td>{{ $line->description }}</td>
                <td class="right">{{ $line->quantity }}</td>
                <td class="right">{{ number_format($line->unit_price / 100, 2) }} {{ $currency }}</td>
                <td class="right">{{ number_format($line->total_price / 100, 2) }} {{ $currency }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr>
                <td>Subtotal</td>
                <td class="right">{{ number_format($order->subtotal / 100, 2) }} {{ $currency }}</td>
            </tr>
            @if($order->discount > 0)
            <tr>
                <td>Discount @if($order->voucher)({{ $order->voucher->code }})@endif</td>
                <td class="right">-{{ number_format($order->discount / 100, 2) }} {{ $currency }}</td>
            </tr>
            @endif
            <tr>
                <td class="total-row">Total Paid</td>
                <td class="right total-row">{{ number_format($order->total / 100, 2) }} {{ $currency }}</td>
            </tr>
        </table>
    </div>

    <div class="paid-stamp">&#10003; PAID</div>

    <div class="footer">
        <div>{{ $org['name'] }} @if($org['address_line1'])&middot; {{ $org['address_line1'] }}@endif @if($org['address_line2'])&middot; {{ $org['address_line2'] }}@endif</div>
        @if($org['registration_id'])<div class="legal">{{ $org['registration_id'] }}</div>@endif
        @if($invoiceFooter)<div class="legal">{!! nl2br(e($invoiceFooter)) !!}</div>@endif
        @if($org['legal_notice'])<div class="legal">{{ $org['legal_notice'] }}</div>@endif
    </div>
</div>
</body>
</html>
