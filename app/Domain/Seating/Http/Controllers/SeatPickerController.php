<?php

namespace App\Domain\Seating\Http\Controllers;

use App\Domain\Event\Models\Event;
use App\Domain\Seating\Actions\AssignSeat;
use App\Domain\Seating\Actions\ReleaseSeat;
use App\Domain\Seating\Http\Requests\StoreSeatAssignmentRequest;
use App\Domain\Seating\Http\Resources\SeatPlanResource;
use App\Domain\Seating\Models\SeatAssignment;
use App\Domain\Seating\Models\SeatPlan;
use App\Domain\Ticketing\Models\Ticket;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\StorageRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * End-user seat picker for an event. Owners, managers and assigned users may pick/change
 * a seat for any user on a ticket they have rights to (see TicketPolicy::pickSeat).
 *
 * @see docs/mil-std-498/SRS.md SET-F-006, SET-F-007, SET-F-008
 * @see docs/mil-std-498/IDD.md §3.14 Seating Picker Endpoints
 */
class SeatPickerController extends Controller
{
    public function __construct(
        private readonly AssignSeat $assignSeat,
        private readonly ReleaseSeat $releaseSeat,
    ) {}

    public function show(Request $request, Event $event): Response
    {
        $event->loadMissing([
            'seatPlans.blocks.seats',
            'seatPlans.blocks.labels',
            'seatPlans.blocks.categoryRestrictions',
            'seatPlans.globalLabels',
        ]);

        $viewer = $request->user();

        $assignments = SeatAssignment::query()
            ->forEvent($event->id)
            ->with(['user', 'ticket.users', 'seat.block'])
            ->get();

        $taken = $assignments->map(function (SeatAssignment $assignment) use ($viewer, $event): array {
            $user = $assignment->user;
            $isVisible = $user->isSeatNameVisibleTo($viewer, $event);

            return [
                'id' => $assignment->id,
                'seat_plan_id' => $assignment->seat_plan_id,
                'seat_id' => $assignment->seat_plan_seat_id,
                'ticket_id' => $assignment->ticket_id,
                'user_id' => $assignment->user_id,
                'name' => $isVisible ? $user->name : null,
                'username' => $isVisible ? $user->username : null,
                'profile_emoji' => $isVisible ? $user->profile_emoji : null,
                'short_bio' => $isVisible ? $user->short_bio : null,
                'avatar_url' => $isVisible ? $user->avatarUrl() : null,
                'banner_url' => $isVisible ? $user->bannerUrl() : null,
            ];
        })->values()->all();

        $myTickets = Ticket::query()
            ->where('event_id', $event->id)
            ->where(function ($query) use ($viewer): void {
                $query->where('owner_id', $viewer->id)
                    ->orWhere('manager_id', $viewer->id)
                    ->orWhereHas('users', fn ($users) => $users->whereKey($viewer->id));
            })
            ->with(['ticketType', 'owner', 'manager', 'users', 'seatAssignments.seat.block'])
            ->get()
            ->map(function (Ticket $ticket) use ($viewer): array {
                $candidates = collect();
                if ($ticket->users->isEmpty() && $ticket->owner) {
                    $candidates->push($ticket->owner);
                } else {
                    $candidates = $candidates->merge($ticket->users);
                }

                $assignees = $candidates->map(function (User $assignee) use ($ticket, $viewer): array {
                    $assignment = $ticket->seatAssignments->firstWhere('user_id', $assignee->id);

                    return [
                        'user_id' => $assignee->id,
                        'name' => $assignee->name,
                        'can_pick' => Gate::forUser($viewer)->allows('pickSeat', [$ticket, $assignee]),
                        'ticket_category_id' => $ticket->ticketType?->ticket_category_id,
                        'assignment' => $assignment ? [
                            'id' => $assignment->id,
                            'seat_plan_id' => $assignment->seat_plan_id,
                            'seat_id' => $assignment->seat_plan_seat_id,
                            'seat_title' => $assignment->seat_title,
                        ] : null,
                    ];
                })->values()->all();

                return [
                    'id' => $ticket->id,
                    'ticket_type_name' => $ticket->ticketType?->name,
                    'is_group' => $ticket->ticketType?->isGroupTicket() ?? false,
                    'assignees' => $assignees,
                ];
            })
            ->values()
            ->all();

        $bannerUrls = array_map(
            fn (string $path) => StorageRole::publicUrl($path),
            array_values(array_filter($event->banner_images ?? [], fn ($p) => is_string($p) && $p !== '')),
        );

        return Inertia::render('seating/Picker', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'banner_image_urls' => $bannerUrls,
            ],
            'seatPlans' => SeatPlanResource::collection($event->seatPlans)->resolve(),
            'taken' => $taken,
            'myTickets' => $myTickets,
            'context' => [
                'ticket_id' => $request->integer('ticket') ?: null,
                'user_id' => $request->integer('user') ?: null,
            ],
        ]);
    }

    public function store(StoreSeatAssignmentRequest $request, Event $event): RedirectResponse
    {
        $validated = $request->validated();

        $ticket = Ticket::with('users')->findOrFail($validated['ticket_id']);
        $assignee = User::findOrFail($validated['user_id']);
        $seatPlan = SeatPlan::findOrFail($validated['seat_plan_id']);

        abort_unless($ticket->event_id === $event->id, 404);

        Gate::authorize('pickSeat', [$ticket, $assignee]);

        $this->assignSeat->execute($ticket, $assignee, $seatPlan, (int) $validated['seat_id']);

        return back()->with('status', 'seat-assigned');
    }

    public function destroy(Request $request, Event $event, SeatAssignment $assignment): RedirectResponse
    {
        $assignment->loadMissing(['ticket.users', 'seatPlan', 'user']);

        abort_unless($assignment->seatPlan->event_id === $event->id, 404);

        Gate::authorize('pickSeat', [$assignment->ticket, $assignment->user]);

        $this->releaseSeat->execute($assignment->ticket, $assignment->user);

        return back()->with('status', 'seat-released');
    }
}
