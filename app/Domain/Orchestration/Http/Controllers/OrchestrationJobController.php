<?php

namespace App\Domain\Orchestration\Http\Controllers;

use App\Domain\Orchestration\Actions\CancelOrchestrationJob;
use App\Domain\Orchestration\Actions\RetryOrchestrationJob;
use App\Domain\Orchestration\Models\OrchestrationJob;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrchestrationJobController extends Controller
{
    public function __construct(
        private readonly RetryOrchestrationJob $retryJob,
        private readonly CancelOrchestrationJob $cancelJob,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', OrchestrationJob::class);

        $query = OrchestrationJob::with('gameServer', 'competition', 'game');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($competitionId = $request->input('competition_id')) {
            $query->where('competition_id', $competitionId);
        }

        $sortColumn = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');
        $query->orderBy($sortColumn, $sortDirection);

        $jobs = $query->paginate($request->input('per_page', 20))->withQueryString();

        return Inertia::render('orchestration/jobs/Index', [
            'jobs' => $jobs,
            'filters' => $request->only(['status', 'competition_id', 'sort', 'direction', 'per_page']),
        ]);
    }

    public function show(OrchestrationJob $orchestrationJob): Response
    {
        $this->authorize('view', $orchestrationJob);

        $orchestrationJob->load('gameServer', 'competition', 'game', 'gameMode', 'chatMessages');

        return Inertia::render('orchestration/jobs/Show', [
            'job' => $orchestrationJob,
        ]);
    }

    public function retry(OrchestrationJob $orchestrationJob): RedirectResponse
    {
        $this->authorize('view', $orchestrationJob);

        $this->retryJob->execute($orchestrationJob);

        return back();
    }

    public function cancel(OrchestrationJob $orchestrationJob): RedirectResponse
    {
        $this->authorize('view', $orchestrationJob);

        $this->cancelJob->execute($orchestrationJob);

        return back();
    }
}
