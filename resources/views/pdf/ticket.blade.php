<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { size: A4; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            width: 210mm;
            height: 297mm;
            margin: 0;
            position: relative;
            font-family: DejaVu Sans, sans-serif;
            color: #1a1a1a;
            line-height: 1.4;
        }

        /* Tri-fold panels: three equal 99 mm bands, each becomes a face when folded. */
        .panel {
            position: absolute;
            left: 0;
            right: 0;
            height: 99mm;
            overflow: hidden;
            padding: 10mm 12mm;
            box-sizing: border-box;
        }
        .panel-top { top: 0; }
        .panel-mid { top: 99mm; }
        .panel-bot { top: 198mm; }

        /* Fold guide lines + label. DomPDF renders dashed borders reliably. */
        .fold-guide {
            position: absolute;
            left: 0;
            right: 0;
            height: 0;
            border-top: 1px dashed #bbb;
        }
        .fold-1 { top: 99mm; }
        .fold-2 { top: 198mm; }

        /* Personalised forensic watermark. Painted first so the panels
           stack on top of it in DomPDF's document-order paint model. */
        .watermark {
            position: absolute;
            top: 0;
            left: 0;
            width: 210mm;
            height: 297mm;
        }
        .fold-label {
            position: absolute;
            right: 4mm;
            top: -3.2mm;
            font-size: 7pt;
            color: #999;
            background: #fff;
            padding: 0 2mm;
        }

        /* Panel 1: hero banner + org identity. */
        .hero {
            position: relative;
            width: 100%;
            height: 62mm;
            background-color: #1a1a1a;
            border-radius: 4mm;
            overflow: hidden;
            margin-bottom: 4mm;
        }
        .hero-img {
            display: block;
            width: 186mm;
            height: auto;
        }
        .hero-text {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            padding: 3mm 6mm 4mm 6mm;
            background-color: #111;
            color: #fff;
        }
        .hero-title { font-size: 18pt; font-weight: bold; }
        .hero-detail { font-size: 10pt; color: #e5e5e5; margin-top: 1mm; }
        .org-row {
            display: table;
            width: 100%;
        }
        .org-row > div { display: table-cell; vertical-align: top; }
        .org-name { font-size: 11pt; font-weight: bold; }
        .org-address { font-size: 7.5pt; color: #666; }
        .ticket-type-label {
            text-align: right;
            font-size: 8pt;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5pt;
        }
        .ticket-type-value {
            text-align: right;
            font-size: 11pt;
            font-weight: bold;
        }

        /* Panel 2: scan face (QR + attendee + extras). */
        .scan-face {
            display: table;
            width: 100%;
            height: 100%;
        }
        .qr-col { display: table-cell; width: 62mm; vertical-align: middle; text-align: center; }
        .qr-col img { width: 55mm; height: 55mm; }
        .qr-ticket-id {
            font-family: monospace;
            font-size: 12pt;
            font-weight: bold;
            letter-spacing: 2pt;
            margin-top: 2mm;
        }
        .qr-scan-note {
            font-size: 7.5pt;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.8pt;
        }
        .info-col {
            display: table-cell;
            vertical-align: middle;
            padding-left: 6mm;
        }
        .info-grid { width: 100%; border-collapse: separate; border-spacing: 0 2mm; }
        .info-label {
            font-size: 7.5pt;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.4pt;
        }
        .info-value {
            font-size: 10pt;
            font-weight: bold;
            margin-top: 0.5mm;
        }
        .addon-row { margin-top: 1mm; }
        .addon-item {
            display: inline-block;
            background: #f2f2f2;
            border-radius: 1.5mm;
            padding: 1mm 2.5mm;
            font-size: 8pt;
            margin: 0 1mm 1mm 0;
        }
        .attendee-list { font-size: 8.5pt; margin-top: 1mm; }
        .attendee-badge {
            display: inline-block;
            background: #e5e5e5;
            border-radius: 1mm;
            padding: 0 1.5mm;
            font-size: 7pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-left: 1mm;
        }

        /* Panel 3: terms + legal footer. */
        .conditions-title {
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5pt;
            margin-bottom: 2mm;
        }
        .condition {
            margin-bottom: 2.5mm;
        }
        .condition-name {
            font-size: 8pt;
            font-weight: bold;
        }
        .condition-content {
            font-size: 6.5pt;
            color: #444;
            line-height: 1.35;
            text-align: justify;
        }
        .legal-footer {
            position: absolute;
            left: 12mm;
            right: 12mm;
            bottom: 6mm;
            font-size: 6.5pt;
            color: #888;
            text-align: center;
            border-top: 1px solid #e5e5e5;
            padding-top: 2mm;
        }
    </style>
</head>
<body>
    @if($watermarkBase64 ?? null)
        <img class="watermark" src="{{ $watermarkBase64 }}" alt="">
    @endif

    <!-- ======================= PANEL 1 (top): hero + org ======================= -->
    <div class="panel panel-top">
        <div class="hero">
            @if($bannerBase64 ?? null)
                <img src="{{ $bannerBase64 }}" alt="" class="hero-img">
            @endif
            <div class="hero-text">
                <div class="hero-title">{{ $ticket->event?->name ?? 'Event' }}</div>
                <div class="hero-detail">
                    @if($ticket->event?->start_date)
                        {{ $ticket->event->start_date->format('l, d M Y') }}
                        @if($ticket->event->end_date && $ticket->event->end_date->format('Y-m-d') !== $ticket->event->start_date->format('Y-m-d'))
                            &ndash; {{ $ticket->event->end_date->format('l, d M Y') }}
                        @endif
                    @endif
                    @if($ticket->event?->venue)
                        &nbsp;&middot;&nbsp; {{ $ticket->event->venue->name }}@if($ticket->event->venue->address), {{ $ticket->event->venue->address->city ?? '' }}@endif
                    @endif
                </div>
            </div>
        </div>

        <div class="org-row">
            <div>
                @if($org['logo_base64'])
                    <img src="{{ $org['logo_base64'] }}" alt="{{ $org['name'] }}" style="max-height: 10mm; max-width: 50mm;"><br>
                @endif
                <span class="org-name">{{ $org['name'] }}</span>
                @if($org['address_line1'])
                    <div class="org-address">{{ $org['address_line1'] }}@if($org['address_line2']), {{ $org['address_line2'] }}@endif</div>
                @endif
            </div>
            <div>
                <div class="ticket-type-label">Ticket Type</div>
                <div class="ticket-type-value">{{ $ticket->ticketType?->name ?? 'General Admission' }}</div>
            </div>
        </div>
    </div>

    <!-- ======================= Fold guide 1 ======================= -->
    <div class="fold-guide fold-1"><span class="fold-label">&#9986; fold here</span></div>

    <!-- ======================= PANEL 2 (middle): scan face ======================= -->
    <div class="panel panel-mid">
        <div class="scan-face">
            <div class="qr-col">
                <img src="{{ $qrCode }}" alt="QR Code">
                <div class="qr-ticket-id">Ticket #{{ $ticket->id }}</div>
                <div class="qr-scan-note">Scan at entrance</div>
            </div>
            <div class="info-col">
                <table class="info-grid">
                    <tr>
                        <td>
                            <div class="info-label">Ticket Holder</div>
                            <div class="info-value">{{ $ticket->owner?->name ?? 'Unassigned' }}</div>
                        </td>
                    </tr>
                    @if($ticket->manager && $ticket->manager_id !== $ticket->owner_id)
                    <tr>
                        <td>
                            <div class="info-label">Managed By</div>
                            <div class="info-value">{{ $ticket->manager->name }}</div>
                        </td>
                    </tr>
                    @endif
                    @if($ticket->order_id)
                    <tr>
                        <td>
                            <div class="info-label">Order</div>
                            <div class="info-value">#{{ $ticket->order_id }}</div>
                        </td>
                    </tr>
                    @endif
                    @if($ticket->users->count() > 1)
                    <tr>
                        <td>
                            <div class="info-label">Assigned Attendees</div>
                            <div class="attendee-list">
                                @foreach($ticket->users as $user)
                                    {{ $user->name }}@if($user->id === $ticket->owner_id)<span class="attendee-badge">Owner</span>@endif @if(!$loop->last), @endif
                                @endforeach
                            </div>
                        </td>
                    </tr>
                    @endif
                    @if($ticket->addons->isNotEmpty())
                    <tr>
                        <td>
                            <div class="info-label">Add-ons</div>
                            <div class="addon-row">
                                @foreach($ticket->addons as $addon)
                                    <span class="addon-item">{{ $addon->name ?? $addon->getTitle() }}</span>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>
    </div>

    <!-- ======================= Fold guide 2 ======================= -->
    <div class="fold-guide fold-2"><span class="fold-label">&#9986; fold here</span></div>

    <!-- ======================= PANEL 3 (bottom): legal + conditions ======================= -->
    <div class="panel panel-bot">
        <div class="conditions-title">Terms &amp; Conditions</div>
        @forelse($conditions as $condition)
            <div class="condition">
                <div class="condition-name">{{ $condition->name }}</div>
                <div class="condition-content">{!! nl2br(e($condition->content)) !!}</div>
            </div>
        @empty
            <div class="condition-content">No purchase conditions are currently configured.</div>
        @endforelse

        <div class="legal-footer">
            {{ $org['name'] }}
            @if($org['address_line1']) &middot; {{ $org['address_line1'] }}@endif
            @if($org['email']) &middot; {{ $org['email'] }}@endif
            @if($org['phone']) &middot; {{ $org['phone'] }}@endif
            @if($org['legal_notice'])<br>{{ $org['legal_notice'] }}@endif
            <br>Ticket #{{ $ticket->id }} &middot; Issued {{ $ticket->created_at->format('d M Y, H:i') }}
        </div>
    </div>
</body>
</html>
