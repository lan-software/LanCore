<?php

namespace App\Domain\Ticketing\Http\Controllers\Api;

use App\Domain\Event\Models\Event;
use App\Domain\Shop\Enums\PaymentMethod;
use App\Domain\Shop\Jobs\GenerateReceiptPdf;
use App\Domain\Shop\Models\Order;
use App\Domain\Ticketing\Actions\UpdateTicketAssignments;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\EntranceAuditLog;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Security\Exceptions\ExpiredTokenException;
use App\Domain\Ticketing\Security\Exceptions\InvalidSignatureException;
use App\Domain\Ticketing\Security\Exceptions\MalformedTokenException;
use App\Domain\Ticketing\Security\Exceptions\UnknownKidException;
use App\Domain\Ticketing\Security\TicketTokenService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EntranceController extends Controller
{
    public function __construct(
        private readonly UpdateTicketAssignments $ticketAssignments,
        private readonly TicketTokenService $tokenService,
    ) {}

    public function validateTicket(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string', 'max:512'],
            'operator_id' => ['required', 'integer'],
            'event_id' => ['sometimes', 'integer'],
        ]);

        $token = $request->input('token');
        [$ticket, $rejectionCode] = $this->resolveDecision($token);
        $auditId = $this->audit($request, 'validate', $ticket, $rejectionCode);

        if ($rejectionCode !== null) {
            return $this->decision($rejectionCode, $this->messageForCode($rejectionCode), $token, auditId: $auditId);
        }

        if (! $ticket) {
            return $this->decision('invalid', 'Ticket not found or invalid.', $token, auditId: $auditId);
        }

        if ($ticket->status === TicketStatus::Cancelled) {
            return $this->decision('invalid', 'This ticket has been cancelled.', $token, $ticket, auditId: $auditId);
        }

        if ($ticket->status === TicketStatus::CheckedIn) {
            return $this->decision('already_checked_in', 'This ticket has already been used for entry.', $token, $ticket, auditId: $auditId);
        }

        if ($rejection = $this->checkEventConstraints($request, $ticket, $token, $auditId)) {
            return $rejection;
        }

        // Check for unpaid on-site order
        if ($ticket->order && $ticket->order->payment_method === PaymentMethod::OnSite && $ticket->order->paid_at === null) {
            return $this->decision('payment_required', 'Payment must be collected before entry.', $token, $ticket, auditId: $auditId, payment: $this->buildPaymentObject($ticket));
        }

        return $this->decision('valid', 'Ticket is valid. Proceed with check-in.', $token, $ticket, auditId: $auditId);
    }

    public function checkin(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string', 'max:512'],
            'operator_id' => ['required', 'integer'],
            'event_id' => ['sometimes', 'integer'],
        ]);

        $resolved = $this->resolveTicketForAction($request);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        $this->ticketAssignments->checkIn($resolved, (int) $request->input('operator_id'));
        $resolved->refresh();

        $auditId = $this->audit($request, 'checkin', $resolved, 'valid');

        return $this->decision('valid', 'Check-in confirmed. Welcome!', $request->input('token'), $resolved, auditId: $auditId, extra: [
            'checkin_id' => 'chk_'.Str::random(8),
        ]);
    }

    public function verifyCheckin(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string', 'max:512'],
            'operator_id' => ['required', 'integer'],
            'event_id' => ['sometimes', 'integer'],
        ]);

        $resolved = $this->resolveTicketForAction($request);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        $this->ticketAssignments->checkIn($resolved, (int) $request->input('operator_id'));
        $resolved->refresh();

        $auditId = $this->audit($request, 'verify_checkin', $resolved, 'valid');

        return $this->decision('valid', 'Verification complete. Check-in confirmed.', $request->input('token'), $resolved, auditId: $auditId, extra: [
            'checkin_id' => 'chk_'.Str::random(8),
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
            'event_id' => ['sometimes', 'integer'],
        ]);

        $token = $request->input('token');
        $ticket = $this->findTicketByToken($token);

        if (! $ticket || $ticket->status === TicketStatus::Cancelled) {
            return response()->json(['error' => 'invalid', 'message' => 'Ticket not found or cancelled.'], 404);
        }

        if ($ticket->status === TicketStatus::CheckedIn) {
            $auditId = $this->audit($request, 'confirm_payment', $ticket, 'already_checked_in');

            return $this->decision('already_checked_in', 'This ticket has already been used.', $token, $ticket, auditId: $auditId);
        }

        if ($rejection = $this->checkEventConstraints($request, $ticket, $token, null)) {
            return $rejection;
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
                'details' => ['expected' => $expectedAmount, 'received' => $receivedAmount],
            ], 422);
        }

        $order->update([
            'paid_at' => now(),
            'confirmed_by' => $request->input('operator_id'),
        ]);

        GenerateReceiptPdf::dispatch($order->id);

        $this->ticketAssignments->checkIn($ticket, (int) $request->input('operator_id'));
        $ticket->refresh();

        $auditId = $this->audit($request, 'confirm_payment', $ticket, 'valid');

        return $this->decision('valid', 'Payment confirmed. Check-in complete.', $token, $ticket, auditId: $auditId, extra: [
            'checkin_id' => 'chk_pay_'.Str::random(4),
            'payment_id' => 'pay_'.Str::random(8),
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
            'event_id' => ['sometimes', 'integer'],
        ]);

        $resolved = $this->resolveTicketForAction($request);
        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        $this->ticketAssignments->checkIn($resolved, (int) $request->input('operator_id'));
        $resolved->refresh();

        $auditId = $this->audit($request, 'override', $resolved, 'valid', $request->input('reason'));

        return $this->decision('valid', 'Override accepted. Check-in confirmed.', $request->input('token'), $resolved, auditId: $auditId, extra: [
            'checkin_id' => 'chk_ovrd_'.Str::random(4),
            'override_id' => 'ovr_'.Str::random(8),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
            'operator_id' => ['required', 'integer'],
            'event_id' => ['sometimes', 'integer'],
        ]);

        $query = $request->input('q');
        $eventId = $request->input('event_id');

        $tickets = Ticket::query()
            ->whereHas('owner', fn ($q) => $q->where('name', 'ilike', "%{$query}%")->orWhere('email', 'ilike', "%{$query}%"))
            ->orWhereHas('users', fn ($q) => $q->where('name', 'ilike', "%{$query}%")->orWhere('email', 'ilike', "%{$query}%"))
            ->when($eventId, fn ($q) => $q->where('event_id', (int) $eventId))
            ->with(['owner:id,name,email', 'ticketType:id,name', 'addons:id,name'])
            ->limit(20)
            ->get();

        $this->audit($request, 'search', null, null, null, ['query' => $query, 'results_count' => $tickets->count()]);

        $results = $tickets->map(fn (Ticket $t) => [
            'token' => null,
            'ticket_id' => $t->id,
            'name' => $t->owner?->name ?? 'Unknown',
            'email' => $t->owner?->email ?? '',
            'ticket_type' => $t->ticketType?->name ?? 'General',
            'validation_token_suffix' => substr((string) ($t->validation_nonce_hash ?? ''), -4),
            'addons' => $t->addons->pluck('name')->all(),
            'seat' => null,
            'status' => $t->status === TicketStatus::CheckedIn ? 'checked_in' : 'not_checked_in',
        ])->all();

        return response()->json(['results' => $results]);
    }

    public function events(): JsonResponse
    {
        $events = Event::query()
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->orderBy('start_date')
            ->get(['id', 'name', 'start_date', 'end_date']);

        return response()->json([
            'events' => $events->map(fn (Event $e) => [
                'id' => $e->id,
                'name' => $e->name,
                'start_date' => $e->start_date?->toIso8601String(),
                'end_date' => $e->end_date?->toIso8601String(),
            ])->all(),
        ]);
    }

    public function stats(): JsonResponse
    {
        $currency = strtoupper((string) config('cashier.currency', 'eur'));

        $totalScans = EntranceAuditLog::where('action', 'validate')->count();
        $checkedIn = EntranceAuditLog::whereIn('action', ['checkin', 'verify_checkin'])->count();
        $denied = EntranceAuditLog::where('action', 'validate')
            ->whereIn('decision', ['invalid', 'already_checked_in', 'denied_by_policy'])
            ->count();
        $overrides = EntranceAuditLog::where('action', 'override')->count();
        $payments = EntranceAuditLog::where('action', 'confirm_payment')->where('decision', 'valid')->count();

        $paymentTotal = Order::query()
            ->whereNotNull('confirmed_by')
            ->sum('total');

        $scansPerHour = EntranceAuditLog::where('action', 'validate')
            ->where('created_at', '>=', now()->subHours(24))
            ->selectRaw("to_char(created_at, 'HH24') as hour, count(*) as count")
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->map(fn ($row) => ['hour' => $row->hour.':00', 'count' => (int) $row->count])
            ->all();

        return response()->json([
            'total_scans' => $totalScans,
            'checked_in' => $checkedIn,
            'denied' => $denied,
            'overrides' => $overrides,
            'payments_collected' => $payments,
            'payment_total' => number_format($paymentTotal / 100, 2, '.', ''),
            'payment_currency' => $currency,
            'avg_checkin_time_ms' => 0,
            'scans_per_hour' => $scansPerHour,
        ]);
    }

    // --- Private helpers ---

    private function findTicketByToken(string $token): ?Ticket
    {
        [$ticket] = $this->resolveDecision($token);

        return $ticket;
    }

    /**
     * @return array{0: ?Ticket, 1: ?string}
     */
    private function resolveDecision(string $token): array
    {
        try {
            $verification = $this->tokenService->verify($token);
        } catch (InvalidSignatureException) {
            return [null, 'invalid_signature'];
        } catch (UnknownKidException) {
            return [null, 'unknown_kid'];
        } catch (ExpiredTokenException) {
            return [null, 'expired'];
        } catch (MalformedTokenException) {
            return [null, 'invalid_signature'];
        }

        $ticket = $this->tokenService->locate($verification);

        if ($ticket === null) {
            return [null, 'revoked'];
        }

        return [$ticket->load(['ticketType', 'owner', 'order.orderLines', 'order.voucher', 'addons', 'users', 'event']), null];
    }

    private function resolveTicketForAction(Request $request): Ticket|JsonResponse
    {
        $token = $request->input('token');
        $ticket = $this->findTicketByToken($token);
        $action = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] ?? 'unknown';

        if (! $ticket) {
            $auditId = $this->audit($request, $action, null, 'invalid');

            return $this->decision('invalid', 'Ticket not found.', $token, auditId: $auditId);
        }

        if ($ticket->status === TicketStatus::Cancelled) {
            $auditId = $this->audit($request, $action, $ticket, 'invalid');

            return $this->decision('invalid', 'This ticket has been cancelled.', $token, $ticket, auditId: $auditId);
        }

        if ($ticket->status === TicketStatus::CheckedIn) {
            $auditId = $this->audit($request, $action, $ticket, 'already_checked_in');

            return $this->decision('already_checked_in', 'This ticket has already been used for entry.', $token, $ticket, auditId: $auditId);
        }

        if ($rejection = $this->checkEventConstraints($request, $ticket, $token, null)) {
            return $rejection;
        }

        return $ticket;
    }

    private function checkEventConstraints(Request $request, Ticket $ticket, string $token, ?string $auditId): ?JsonResponse
    {
        $eventId = $request->input('event_id');

        if ($eventId && $ticket->event_id !== (int) $eventId) {
            $aid = $auditId ?? $this->audit($request, 'validate', $ticket, 'invalid');

            return $this->decision('invalid', 'This ticket is not for the selected event.', $token, $ticket, auditId: $aid);
        }

        if ($ticket->event?->end_date && $ticket->event->end_date->isPast()) {
            $aid = $auditId ?? $this->audit($request, 'validate', $ticket, 'invalid');

            return $this->decision('invalid', 'This event has already ended.', $token, $ticket, auditId: $aid);
        }

        return null;
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
        $response = [
            'decision' => $decision,
            'message' => $message,
            'attendee' => $ticket?->owner ? ['name' => $ticket->owner->name, 'group' => null] : null,
            'seating' => null,
            'addons' => $ticket && $ticket->addons->isNotEmpty()
                ? $ticket->addons->map(fn ($a) => ['name' => $a->name ?? $a->getTitle(), 'info' => null])->all()
                : null,
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

    private function messageForCode(string $code): string
    {
        return match ($code) {
            'invalid_signature' => 'Token signature is invalid.',
            'unknown_kid' => 'Token signing key is not recognised.',
            'expired' => 'This ticket token has expired.',
            'revoked' => 'This ticket token is no longer valid.',
            default => 'Ticket not found or invalid.',
        };
    }

    private function buildPaymentObject(Ticket $ticket): array
    {
        $order = $ticket->order;
        $currency = strtoupper((string) config('cashier.currency', 'eur'));

        return [
            'amount' => number_format($order->total / 100, 2, '.', ''),
            'currency' => $currency,
            'items' => $order->orderLines->map(fn ($line) => [
                'name' => $line->description,
                'price' => number_format($line->total_price / 100, 2, '.', ''),
            ])->all(),
            'methods' => ['cash', 'card'],
        ];
    }

    private function audit(Request $request, string $action, ?Ticket $ticket, ?string $decision = null, ?string $overrideReason = null, ?array $metadata = null): string
    {
        $auditId = 'aud_'.Str::random(8);

        $token = (string) $request->input('token');
        $fingerprint = $token !== '' ? substr(hash('sha256', $token), 0, 16) : null;

        EntranceAuditLog::create([
            'ticket_id' => $ticket?->id,
            'token_fingerprint' => $fingerprint,
            'action' => $action,
            'decision' => $decision ?? $action,
            'operator_id' => $request->input('operator_id', 0),
            'operator_session' => $request->input('operator_session'),
            'client_info' => $request->input('client_info'),
            'override_reason' => $overrideReason,
            'metadata' => array_merge($metadata ?? [], ['audit_id' => $auditId]),
            'created_at' => now(),
        ]);

        return $auditId;
    }
}
