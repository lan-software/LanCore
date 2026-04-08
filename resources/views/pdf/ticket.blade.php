<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; line-height: 1.5; }
        .container { padding: 30px 40px; }
        .header { margin-bottom: 20px; }
        .org-name { font-size: 14px; font-weight: bold; }
        .org-detail { font-size: 8px; color: #666; }
        .event-banner { background: #1a1a1a; color: #fff; padding: 14px 20px; border-radius: 6px; margin-bottom: 24px; }
        .event-name { font-size: 16px; font-weight: bold; }
        .event-detail { font-size: 10px; color: #ccc; margin-top: 2px; }
        .ticket-body { text-align: center; margin-bottom: 24px; }
        .qr-code { margin: 0 auto 12px; }
        .qr-code img { width: 200px; height: 200px; }
        .validation-id { font-family: monospace; font-size: 18px; font-weight: bold; letter-spacing: 3px; color: #333; }
        .validation-label { font-size: 8px; color: #999; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; }
        .info-grid { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .info-grid td { padding: 8px 12px; vertical-align: top; width: 50%; }
        .info-card { border: 1px solid #e5e5e5; border-radius: 4px; padding: 10px 12px; }
        .info-label { font-size: 8px; color: #999; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 3px; }
        .info-value { font-size: 11px; font-weight: 600; }
        .addons { margin-bottom: 20px; }
        .addons-title { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #666; margin-bottom: 6px; }
        .addon-item { display: inline-block; background: #f5f5f5; border-radius: 3px; padding: 4px 10px; font-size: 10px; margin: 2px 4px 2px 0; }
        .attendees { margin-bottom: 20px; }
        .attendee { font-size: 10px; padding: 3px 0; }
        .attendee-badge { background: #e5e5e5; border-radius: 2px; padding: 1px 6px; font-size: 8px; font-weight: 600; text-transform: uppercase; }
        .footer { border-top: 1px solid #ddd; padding-top: 12px; font-size: 7px; color: #999; text-align: center; }
        .footer .legal { margin-top: 3px; }
        .ticket-meta { font-size: 8px; color: #bbb; text-align: center; margin-bottom: 16px; }
    </style>
</head>
<body>
<div class="container">
    <!-- Header -->
    <div class="header">
        <table style="width: 100%;">
            <tr>
                <td>
                    @if($org['logo_base64'])
                        <img src="{{ $org['logo_base64'] }}" alt="{{ $org['name'] }}" style="max-height: 36px; max-width: 140px; margin-bottom: 4px;"><br>
                    @endif
                    <span class="org-name">{{ $org['name'] }}</span>
                </td>
                <td style="text-align: right; vertical-align: top;">
                    <div style="font-size: 9px; color: #999;">TICKET</div>
                    <div style="font-size: 10px; font-weight: bold;">{{ $ticket->ticketType?->name ?? 'General Admission' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Event Banner -->
    @if($ticket->event)
    <div class="event-banner">
        <div class="event-name">{{ $ticket->event->name }}</div>
        <div class="event-detail">
            @if($ticket->event->start_date)
                {{ $ticket->event->start_date->format('l, d M Y') }}
                @if($ticket->event->end_date && $ticket->event->end_date->format('Y-m-d') !== $ticket->event->start_date->format('Y-m-d'))
                    &ndash; {{ $ticket->event->end_date->format('l, d M Y') }}
                @endif
            @endif
            @if($ticket->event->venue)
                &nbsp;&middot;&nbsp; {{ $ticket->event->venue->name }}
                @if($ticket->event->venue->address)
                    , {{ $ticket->event->venue->address->city ?? '' }}
                @endif
            @endif
        </div>
    </div>
    @endif

    <!-- QR Code -->
    <div class="ticket-body">
        <div class="qr-code">
            <img src="{{ $qrCode }}" alt="QR Code">
        </div>
        <div class="validation-label">Scan at entrance</div>
        <div class="validation-id">Ticket #{{ $ticket->id }}</div>
    </div>

    <!-- Ticket info grid -->
    <table class="info-grid">
        <tr>
            <td>
                <div class="info-card">
                    <div class="info-label">Ticket Holder</div>
                    <div class="info-value">{{ $ticket->owner?->name ?? 'Unassigned' }}</div>
                </div>
            </td>
            <td>
                <div class="info-card">
                    <div class="info-label">Ticket Type</div>
                    <div class="info-value">{{ $ticket->ticketType?->name ?? '—' }}</div>
                </div>
            </td>
        </tr>
        @if($ticket->manager && $ticket->manager_id !== $ticket->owner_id)
        <tr>
            <td>
                <div class="info-card">
                    <div class="info-label">Managed By</div>
                    <div class="info-value">{{ $ticket->manager->name }}</div>
                </div>
            </td>
            <td>
                <div class="info-card">
                    <div class="info-label">Order</div>
                    <div class="info-value">#{{ $ticket->order_id }}</div>
                </div>
            </td>
        </tr>
        @endif
    </table>

    <!-- Assigned Users -->
    @if($ticket->users->count() > 1)
    <div class="attendees">
        <div class="addons-title">Assigned Attendees</div>
        @foreach($ticket->users as $user)
        <div class="attendee">
            {{ $user->name }}
            @if($user->id === $ticket->owner_id)
                <span class="attendee-badge">Owner</span>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    <!-- Addons -->
    @if($ticket->addons->isNotEmpty())
    <div class="addons">
        <div class="addons-title">Included Extras</div>
        @foreach($ticket->addons as $addon)
            <span class="addon-item">{{ $addon->name ?? $addon->getTitle() }}</span>
        @endforeach
    </div>
    @endif

    <!-- Meta -->
    <div class="ticket-meta">
        Ticket #{{ $ticket->id }} &middot; Issued {{ $ticket->created_at->format('d M Y, H:i') }}
    </div>

    <!-- Footer -->
    <div class="footer">
        <div>{{ $org['name'] }} @if($org['address_line1'])&middot; {{ $org['address_line1'] }}@endif @if($org['address_line2'])&middot; {{ $org['address_line2'] }}@endif</div>
        @if($org['email'] || $org['phone'])
        <div class="legal">{{ $org['email'] }} @if($org['phone'])&middot; {{ $org['phone'] }}@endif</div>
        @endif
        @if($org['legal_notice'])<div class="legal">{{ $org['legal_notice'] }}</div>@endif
    </div>
</div>
</body>
</html>
