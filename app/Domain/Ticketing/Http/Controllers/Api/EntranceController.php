<?php

namespace App\Domain\Ticketing\Http\Controllers\Api;

use App\Domain\Shop\Enums\PaymentMethod;
use App\Domain\Shop\Jobs\GenerateReceiptPdf;
use App\Domain\Ticketing\Actions\UpdateTicketAssignments;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\EntranceAuditLog;
use App\Domain\Ticketing\Models\Ticket;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EntranceController extends Controller
{
    public function __construct(
        private readonly UpdateTicketAssignments $ticketAssignments,
    ) {}

    public function validateTicket(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string', 'max:512'],
            'operator_id' => ['required', 'integer'],
        ]);

        $token = $request->input('token');
        $ticket = Ticket::where('validation_id', $token)
            ->with(['ticketType', 'owner', 'order.orderLines', 'order.voucher', 'addons', 'users'])
            ->first();

        $auditId = $this->audit($request, 'validate', $ticket);

        if (! $ticket) {
            return $this->decision('invalid', 'Ticket not found or invalid.', $token, auditId: $auditId);
        }

        if ($ticket->status === TicketStatus::Cancelled) {
            return $this->decision('invalid', 'This ticket has been cancelled.', $token, $ticket, auditId: $auditId);
        }

        if ($ticket->status === TicketStatus::CheckedIn) {
            return $this->decision('already_checked_in', 'This ticket has already been used for entry.', $token, $ticket, auditId: $auditId);
        }

        // Check for unpaid on-site order
        if ($ticket->order && $ticket->order->payment_method === PaymentMethod::OnSite && $ticket->order->paid_at === null) {
            $payment = $this->buildPaymentObject($ticket);

            return $this->decision('payment_required', 'Payment must be collected before entry.', $token, $ticket, auditId: $auditId, payment: $payment);
        }

        return $this->decision('valid', 'Ticket is valid. Proceed with check-in.', $token, $ticket, auditId: $auditId);
    }

    public function checkin(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string', 'max:512'],
            'validation_id' => ['required', 'string'],
            'operator_id' => ['required', 'integer'],
        ]);

        $ticket = $this->findActiveTicket($request->input('token'));
        if (! $ticket) {
            return response()->json(['error' => 'invalid', 'message' => 'Ticket not found or not valid for check-in.'], 404);
        }

        $this->ticketAssignments->checkIn($ticket, (int) $request->input('operator_id'));
        $ticket->refresh();

        $auditId = $this->audit($request, 'checkin', $ticket, 'valid');
        $checkinId = 'chk_'.Str::random(8);

        return $this->decision('valid', 'Check-in confirmed. Welcome!', $request->input('token'), $ticket, auditId: $auditId, extra: [
            'checkin_id' => $checkinId,
        ]);
    }

    public function verifyCheckin(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string', 'max:512'],
            'validation_id' => ['required', 'string'],
            'operator_id' => ['required', 'integer'],
        ]);

        $ticket = $this->findActiveTicket($request->input('token'));
        if (! $ticket) {
            return response()->json(['error' => 'invalid', 'message' => 'Ticket not found or not valid for check-in.'], 404);
        }

        $this->ticketAssignments->checkIn($ticket, (int) $request->input('operator_id'));
        $ticket->refresh();

        $auditId = $this->audit($request, 'verify_checkin', $ticket, 'valid');
        $checkinId = 'chk_'.Str::random(8);

        return $this->decision('valid', 'Verification complete. Check-in confirmed.', $request->input('token'), $ticket, auditId: $auditId, extra: [
            'checkin_id' => $checkinId,
        ]);
    }

    public function confirmPayment(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string', 'max:512'],
            'validation_id' => ['required', 'string'],
            'payment_method' => ['required', 'string'],
            'amount' => ['required', 'string'],
            'operator_id' => ['required', 'integer'],
        ]);

        $ticket = Ticket::where('validation_id', $request->input('token'))
            ->with(['ticketType', 'owner', 'order.orderLines', 'order.voucher', 'addons', 'users'])
            ->first();

        if (! $ticket || $ticket->status !== TicketStatus::Active) {
            return response()->json(['error' => 'invalid', 'message' => 'Ticket not found or not valid for payment.'], 404);
        }

        $order = $ticket->order;
        if (! $order || $order->paid_at !== null) {
            return response()->json(['error' => 'invalid', 'message' => 'No outstanding payment for this ticket.'], 422);
        }

        $expectedAmount = number_format($order->total / 100, 2, '.', '');
        $receivedAmount = $request->input('amount');

        if ($expectedAmount !== $receivedAmount) {
            return response()->json([
                'error' => 'amount_mismatch',
                'message' => 'Confirmed amount does not match the outstanding balance.',
                'details' => [
                    'expected' => $expectedAmount,
                    'received' => $receivedAmount,
                ],
            ], 422);
        }

        $operatorId = $request->input('operator_id');

        $order->update([
            'paid_at' => now(),
            'confirmed_by' => $operatorId,
        ]);

        GenerateReceiptPdf::dispatch($order->id);

        $this->ticketAssignments->checkIn($ticket, (int) $request->input('operator_id'));
        $ticket->refresh();

        $auditId = $this->audit($request, 'confirm_payment', $ticket, 'valid');
        $checkinId = 'chk_pay_'.Str::random(4);
        $paymentId = 'pay_'.Str::random(8);

        return $this->decision('valid', 'Payment confirmed. Check-in complete.', $request->input('token'), $ticket, auditId: $auditId, extra: [
            'checkin_id' => $checkinId,
            'payment_id' => $paymentId,
            'receipt_sent' => true,
        ]);
    }

    public function override(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string', 'max:512'],
            'validation_id' => ['required', 'string'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
            'operator_id' => ['required', 'integer'],
        ]);

        $ticket = $this->findActiveTicket($request->input('token'));
        if (! $ticket) {
            return response()->json(['error' => 'invalid', 'message' => 'Ticket not found or not valid for override.'], 404);
        }

        $this->ticketAssignments->checkIn($ticket, (int) $request->input('operator_id'));
        $ticket->refresh();

        $auditId = $this->audit($request, 'override', $ticket, 'valid', $request->input('reason'));
        $checkinId = 'chk_ovrd_'.Str::random(4);
        $overrideId = 'ovr_'.Str::random(8);

        return $this->decision('valid', 'Override accepted. Check-in confirmed.', $request->input('token'), $ticket, auditId: $auditId, extra: [
            'checkin_id' => $checkinId,
            'override_id' => $overrideId,
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
            'operator_id' => ['required', 'integer'],
        ]);

        $query = $request->input('q');

        $tickets = Ticket::query()
            ->where(function ($q) use ($query) {
                $q->where('validation_id', 'ilike', "%{$query}%")
                    ->orWhereHas('owner', fn ($q) => $q->where('name', 'ilike', "%{$query}%")->orWhere('email', 'ilike', "%{$query}%"));
            })
            ->with('owner:id,name')
            ->limit(20)
            ->get();

        $this->audit($request, 'search', null, null, null, ['query' => $query, 'results_count' => $tickets->count()]);

        $results = $tickets->map(fn (Ticket $t) => [
            'token' => $t->validation_id,
            'name' => $t->owner?->name ?? 'Unknown',
            'status' => $t->status === TicketStatus::CheckedIn ? 'checked_in' : 'not_checked_in',
            'seat' => null,
            'group' => null,
        ])->all();

        return response()->json(['results' => $results]);
    }

    public function stats(): JsonResponse
    {
        $currency = strtoupper((string) config('cashier.currency', 'eur'));

        $totalScans = EntranceAuditLog::where('action', 'validate')->count();
        $checkedIn = EntranceAuditLog::where('action', 'checkin')
            ->orWhere('action', 'verify_checkin')
            ->count();
        $denied = EntranceAuditLog::where('action', 'validate')
            ->whereIn('decision', ['invalid', 'already_checked_in', 'denied_by_policy'])
            ->count();
        $overrides = EntranceAuditLog::where('action', 'override')->count();
        $payments = EntranceAuditLog::where('action', 'confirm_payment')->count();

        // Payment totals from orders confirmed via entrance
        $paymentTotal = \App\Domain\Shop\Models\Order::query()
            ->whereNotNull('confirmed_by')
            ->sum('total');

        // Avg check-in time: time between validate and checkin for same validation_id
        $avgTime = 0;

        // Scans per hour (last 24 hours)
        $scansPerHour = EntranceAuditLog::where('action', 'validate')
            ->where('created_at', '>=', now()->subHours(24))
            ->selectRaw("to_char(created_at, 'HH24') as hour, count(*) as count")
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->map(fn ($row) => [
                'hour' => $row->hour.':00',
                'count' => (int) $row->count,
            ])
            ->all();

        return response()->json([
            'total_scans' => $totalScans,
            'checked_in' => $checkedIn,
            'denied' => $denied,
            'overrides' => $overrides,
            'payments_collected' => $payments,
            'payment_total' => number_format($paymentTotal / 100, 2, '.', ''),
            'payment_currency' => $currency,
            'avg_checkin_time_ms' => $avgTime,
            'scans_per_hour' => $scansPerHour,
        ]);
    }

    // --- Private helpers ---

    private function findActiveTicket(string $token): ?Ticket
    {
        $ticket = Ticket::where('validation_id', $token)
            ->with(['ticketType', 'owner', 'order', 'addons', 'users'])
            ->first();

        if (! $ticket || $ticket->status !== TicketStatus::Active) {
            return null;
        }

        return $ticket;
    }

    private function decision(
        string $decision,
        string $message,
        string $token,
        ?Ticket $ticket = null,
        ?string $auditId = null,
        ?array $payment = null,
        array $extra = [],
    ): JsonResponse {
        $validationId = 'val_'.Str::random(8);

        $response = [
            'decision' => $decision,
            'message' => $message,
            'validation_id' => $validationId,
            'attendee' => $ticket?->owner ? [
                'name' => $ticket->owner->name,
                'group' => null,
            ] : null,
            'seating' => null,
            'addons' => $ticket ? $this->buildAddons($ticket) : null,
            'verification' => null,
            'payment' => $payment,
            'override_allowed' => $decision === 'override_possible',
            'audit_id' => $auditId,
            'group_policy' => null,
            'degraded' => false,
            ...$extra,
        ];

        return response()->json($response);
    }

    /**
     * @return array<int, array{name: string, info: string|null}>|null
     */
    private function buildAddons(Ticket $ticket): ?array
    {
        if ($ticket->addons->isEmpty()) {
            return null;
        }

        return $ticket->addons->map(fn ($addon) => [
            'name' => $addon->name ?? $addon->getTitle(),
            'info' => null,
        ])->all();
    }

    /**
     * @return array{amount: string, currency: string, items: array, methods: array}
     */
    private function buildPaymentObject(Ticket $ticket): array
    {
        $order = $ticket->order;
        $currency = strtoupper((string) config('cashier.currency', 'eur'));

        $items = $order->orderLines->map(fn ($line) => [
            'name' => $line->description,
            'price' => number_format($line->total_price / 100, 2, '.', ''),
        ])->all();

        return [
            'amount' => number_format($order->total / 100, 2, '.', ''),
            'currency' => $currency,
            'items' => $items,
            'methods' => ['cash', 'card'],
        ];
    }

    private function audit(Request $request, string $action, ?Ticket $ticket, ?string $decision = null, ?string $overrideReason = null, ?array $metadata = null): string
    {
        $auditId = 'aud_'.Str::random(8);

        EntranceAuditLog::create([
            'ticket_id' => $ticket?->id,
            'validation_id' => $ticket?->validation_id ?? $request->input('token'),
            'action' => $action,
            'decision' => $decision ?? $action,
            'operator_id' => $request->input('operator_id', 0),
            'operator_session' => $request->input('operator_session'),
            'client_info' => $request->input('client_info'),
            'override_reason' => $overrideReason,
            'metadata' => $metadata ? array_merge($metadata, ['audit_id' => $auditId]) : ['audit_id' => $auditId],
            'created_at' => now(),
        ]);

        return $auditId;
    }
}
