<?php

namespace App\Domain\Ticketing\Http\Controllers;

use App\Domain\Ticketing\Actions\UpdateTicketAssignments;
use App\Domain\Ticketing\Http\Requests\AdminTicketIndexRequest;
use App\Domain\Ticketing\Models\Ticket;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @see docs/mil-std-498/SRS.md TKT-F-010
 */
class AdminTicketController extends Controller
{
    public function index(AdminTicketIndexRequest $request): Response
    {
        $this->authorize('viewAny', Ticket::class);

        $query = Ticket::with(['ticketType', 'event', 'owner', 'order']);

        if ($search = $request->validated('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('owner', fn ($q) => $q->whereLike('name', "%{$search}%")->orWhereLike('email', "%{$search}%"))
                    ->orWhereHas('ticketType', fn ($q) => $q->whereLike('name', "%{$search}%"));

                if (ctype_digit((string) $search)) {
                    $q->orWhere('id', (int) $search);
                }
            });
        }

        if ($status = $request->validated('status')) {
            $query->where('status', $status);
        }

        $sortColumn = $request->validated('sort') ?? 'created_at';
        $sortDirection = $request->validated('direction') ?? 'desc';
        $query->orderBy($sortColumn, $sortDirection);

        $tickets = $query->paginate($request->validated('per_page') ?? 20)->withQueryString();

        return Inertia::render('admin-tickets/Index', [
            'tickets' => $tickets,
            'filters' => $request->only(['search', 'sort', 'direction', 'status', 'per_page']),
        ]);
    }

    public function show(Ticket $ticket): Response
    {
        $this->authorize('view', $ticket);

        $ticket->load([
            'ticketType.ticketCategory',
            'event',
            'order.user',
            'owner',
            'manager',
            'users',
            'addons',
        ]);

        return Inertia::render('admin-tickets/Show', [
            'ticket' => $ticket,
            'validation_token' => [
                'kid' => $ticket->validation_kid,
                'issued_at' => $ticket->validation_issued_at?->toIso8601String(),
                'expires_at' => $ticket->validation_expires_at?->toIso8601String(),
                'status' => $this->tokenStatus($ticket),
            ],
        ]);
    }

    public function rotateToken(Ticket $ticket, UpdateTicketAssignments $action): RedirectResponse
    {
        $this->authorize('update', $ticket);

        $action->rotateToken($ticket);

        return back()->with('success', __('ticketing.admin.token_rotated'));
    }

    private function tokenStatus(Ticket $ticket): string
    {
        if ($ticket->validation_nonce_hash === null) {
            return 'Revoked';
        }

        if ($ticket->validation_expires_at && $ticket->validation_expires_at->isPast()) {
            return 'Expired';
        }

        return 'Active';
    }
}
