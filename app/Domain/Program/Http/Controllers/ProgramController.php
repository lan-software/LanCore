<?php

namespace App\Domain\Program\Http\Controllers;

use App\Domain\Event\Models\Event;
use App\Domain\Program\Actions\CreateProgram;
use App\Domain\Program\Actions\DeleteProgram;
use App\Domain\Program\Actions\UpdateProgram;
use App\Domain\Program\Http\Requests\ProgramIndexRequest;
use App\Domain\Program\Http\Requests\StoreProgramRequest;
use App\Domain\Program\Http\Requests\UpdateProgramRequest;
use App\Domain\Program\Models\Program;
use App\Domain\Sponsoring\Models\Sponsor;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @see docs/mil-std-498/SSS.md CAP-PRG-001, CAP-PRG-002
 * @see docs/mil-std-498/SRS.md PRG-F-001, PRG-F-002, PRG-F-003
 */
class ProgramController extends Controller
{
    public function __construct(
        private readonly CreateProgram $createProgram,
        private readonly UpdateProgram $updateProgram,
        private readonly DeleteProgram $deleteProgram,
    ) {}

    public function index(ProgramIndexRequest $request): Response
    {
        $this->authorize('viewAny', Program::class);

        $query = Program::with('event');

        if ($search = $request->validated('search')) {
            $query->where('name', 'ilike', "%{$search}%");
        }

        $eventId = $request->validated('event_id') ?? $request->session()->get('selected_event_id');
        if ($eventId) {
            $query->where(fn ($q) => $q->where('event_id', $eventId)->orWhereNull('event_id'));
        }

        $sortColumn = $request->validated('sort') ?? 'created_at';
        $sortDirection = $request->validated('direction') ?? 'desc';
        $query->orderBy($sortColumn, $sortDirection);

        $programs = $query->paginate($request->validated('per_page') ?? 20)->withQueryString();

        return Inertia::render('programs/Index', [
            'programs' => $programs,
            'filters' => $request->only(['search', 'sort', 'direction', 'event_id', 'per_page']),
            'events' => Event::with('primaryProgram:id,name')
                ->orderBy('name')
                ->get(['id', 'name', 'primary_program_id']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Program::class);

        return Inertia::render('programs/Create', [
            'events' => Event::with('primaryProgram:id,name')
                ->orderBy('name')
                ->get(['id', 'name', 'primary_program_id']),
            'selectedEventId' => session('selected_event_id'),
        ]);
    }

    public function store(StoreProgramRequest $request): RedirectResponse
    {
        $this->authorize('create', Program::class);

        $program = $this->createProgram->execute(
            $request->safe()->only(['name', 'description', 'visibility', 'event_id']),
            $request->validated('time_slots', []),
        );

        if ($request->boolean('is_primary')) {
            Event::where('id', $program->event_id)->update(['primary_program_id' => $program->id]);
        }

        return redirect()->route('programs.index');
    }

    public function edit(Program $program): Response
    {
        $this->authorize('update', $program);

        $program->load(['event', 'timeSlots.sponsors', 'sponsors']);
        $isPrimary = $program->event->primary_program_id === $program->id;

        return Inertia::render('programs/Edit', [
            'program' => $program,
            'isPrimary' => $isPrimary,
            'events' => Event::with('primaryProgram:id,name')
                ->orderBy('name')
                ->get(['id', 'name', 'primary_program_id']),
            'sponsors' => Sponsor::with('sponsorLevel')
                ->orderBy('name')
                ->get(['id', 'name', 'sponsor_level_id']),
        ]);
    }

    public function update(UpdateProgramRequest $request, Program $program): RedirectResponse
    {
        $this->authorize('update', $program);

        $this->updateProgram->execute(
            $program,
            $request->safe()->only(['name', 'description', 'visibility']),
            $request->validated('time_slots', []),
            $request->validated('sponsor_ids'),
        );

        $event = $program->event;
        if ($request->boolean('is_primary')) {
            $event->update(['primary_program_id' => $program->id]);
        } elseif ($event->primary_program_id === $program->id) {
            $event->update(['primary_program_id' => null]);
        }

        return back();
    }

    public function destroy(Program $program): RedirectResponse
    {
        $this->authorize('delete', $program);

        $this->deleteProgram->execute($program);

        return redirect()->route('programs.index');
    }
}
