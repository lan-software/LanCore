<?php

namespace App\Domain\Ticketing\Http\Controllers;

use App\Domain\Ticketing\Actions\UpdateTicketAssignments;
use App\Domain\Ticketing\Models\Ticket;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    public function __construct(
        private readonly UpdateTicketAssignments $updateTicketAssignments,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        $ownedTickets = $user->ownedTickets()
            ->with(['ticketType', 'event', 'manager', 'ticketUser', 'addons'])
            ->get();

        $managedTickets = $user->managedTickets()
            ->where('owner_id', '!=', $user->id)
            ->with(['ticketType', 'event', 'owner', 'ticketUser', 'addons'])
            ->get();

        $usableTickets = $user->usableTickets()
            ->where('owner_id', '!=', $user->id)
            ->where(function ($query) use ($user) {
                $query->where('manager_id', '!=', $user->id)
                    ->orWhereNull('manager_id');
            })
            ->with(['ticketType', 'event', 'addons'])
            ->get();

        return Inertia::render('tickets/Index', [
            'ownedTickets' => $ownedTickets,
            'managedTickets' => $managedTickets,
            'usableTickets' => $usableTickets,
        ]);
    }

    public function show(Ticket $ticket): Response
    {
        $this->authorize('view', $ticket);

        return Inertia::render('tickets/Show', [
            'ticket' => $ticket->load([
                'ticketType.ticketCategory',
                'event',
                'order',
                'owner',
                'manager',
                'ticketUser',
                'addons',
            ]),
        ]);
    }

    public function updateManager(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('updateManager', $ticket);

        $request->validate([
            'manager_email' => ['nullable', 'email', 'exists:users,email'],
        ]);

        $manager = $request->input('manager_email')
            ? User::where('email', $request->input('manager_email'))->firstOrFail()
            : null;

        $this->updateTicketAssignments->updateManager($ticket, $manager, $request->user()->id);

        return back();
    }

    public function updateUser(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('updateUser', $ticket);

        $request->validate([
            'user_email' => ['nullable', 'email', 'exists:users,email'],
        ]);

        $ticketUser = $request->input('user_email')
            ? User::where('email', $request->input('user_email'))->firstOrFail()
            : null;

        $this->updateTicketAssignments->updateUser($ticket, $ticketUser, $request->user()->id);

        return back();
    }
}
