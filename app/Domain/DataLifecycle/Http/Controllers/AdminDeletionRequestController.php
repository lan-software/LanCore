<?php

namespace App\Domain\DataLifecycle\Http\Controllers;

use App\Domain\DataLifecycle\Actions\AnonymizeUser;
use App\Domain\DataLifecycle\Actions\ApplyRetentionHolds;
use App\Domain\DataLifecycle\Actions\CancelUserDeletion;
use App\Domain\DataLifecycle\Actions\ConfirmUserDeletion;
use App\Domain\DataLifecycle\Actions\ForceDeleteUserData;
use App\Domain\DataLifecycle\Actions\RequestUserDeletion;
use App\Domain\DataLifecycle\Enums\DeletionInitiator;
use App\Domain\DataLifecycle\Http\Requests\AdminAnonymizeImmediatelyRequest;
use App\Domain\DataLifecycle\Http\Requests\AdminRequestDeletionRequest;
use App\Domain\DataLifecycle\Http\Requests\ForceDeleteUserDataRequest;
use App\Domain\DataLifecycle\Models\DeletionRequest;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin queue + admin-induced flows.
 *
 * @see docs/mil-std-498/SSS.md CAP-DL-002, CAP-DL-006
 * @see docs/mil-std-498/SRS.md DL-F-002, DL-F-014, DL-F-015
 */
class AdminDeletionRequestController extends Controller
{
    public function __construct(
        private readonly RequestUserDeletion $requestUserDeletion,
        private readonly ConfirmUserDeletion $confirmUserDeletion,
        private readonly CancelUserDeletion $cancelUserDeletion,
        private readonly AnonymizeUser $anonymizeUser,
        private readonly ForceDeleteUserData $forceDeleteUserData,
        private readonly ApplyRetentionHolds $applyRetentionHolds,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', DeletionRequest::class);

        $requests = DeletionRequest::query()
            ->with(['user' => fn ($q) => $q->withTrashed(), 'requestedByAdmin'])
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('admin/data-lifecycle/DeletionRequests/Index', [
            'requests' => $requests,
        ]);
    }

    public function show(Request $request, DeletionRequest $deletionRequest): Response
    {
        $this->authorize('view', $deletionRequest);

        $subject = $deletionRequest->user;
        $verdicts = $subject !== null
            ? $this->applyRetentionHolds->execute($subject)
            : [];

        return Inertia::render('admin/data-lifecycle/DeletionRequests/Show', [
            'deletionRequest' => $deletionRequest->load([
                'user' => fn ($q) => $q->withTrashed(),
                'requestedByAdmin',
            ]),
            'retentionVerdicts' => collect($verdicts)
                ->map(fn ($v, $key) => [
                    'data_class' => $key,
                    'holds' => $v->holds,
                    'until' => $v->until?->toIso8601String(),
                    'basis' => $v->basis,
                ])
                ->values(),
        ]);
    }

    public function store(AdminRequestDeletionRequest $request): RedirectResponse
    {
        $subject = User::findOrFail($request->validated('user_id'));

        $this->requestUserDeletion->execute(
            subject: $subject,
            initiator: DeletionInitiator::Admin,
            reason: $request->validated('reason'),
            requestedByAdmin: $request->user(),
        );

        return back()->with('status', 'Deletion request created and confirmation email sent to the user.');
    }

    public function anonymizeNow(Request $request, DeletionRequest $deletionRequest): RedirectResponse
    {
        $this->authorize('anonymizeNow', $deletionRequest);

        $this->anonymizeUser->execute($deletionRequest);

        return back()->with('status', 'User has been anonymized.');
    }

    public function cancel(Request $request, DeletionRequest $deletionRequest): RedirectResponse
    {
        $this->authorize('cancel', $deletionRequest);

        $this->cancelUserDeletion->execute($deletionRequest);

        return back()->with('status', 'Deletion request cancelled.');
    }

    public function forceDelete(ForceDeleteUserDataRequest $request, User $user): RedirectResponse
    {
        $this->forceDeleteUserData->execute(
            subject: $user,
            admin: $request->user(),
            reason: $request->validated('reason'),
        );

        return redirect()
            ->route('admin.data-lifecycle.deletion-requests.index')
            ->with('status', 'User and all force-deletable data have been permanently removed.');
    }

    /**
     * One-shot admin flow: open a deletion request, mark the email confirmation
     * as satisfied, and run the anonymizer immediately. Mirrors the CLI's
     * `lifecycle:user:delete --immediate` for testing and operator-driven
     * deletions where waiting for the user to click the email is impractical.
     */
    public function anonymizeImmediately(AdminAnonymizeImmediatelyRequest $request, User $user): RedirectResponse
    {
        $deletionRequest = DB::transaction(function () use ($request, $user): DeletionRequest {
            $opened = $this->requestUserDeletion->execute(
                subject: $user,
                initiator: DeletionInitiator::Admin,
                reason: $request->validated('reason'),
                requestedByAdmin: $request->user(),
            );

            $confirmed = $this->confirmUserDeletion->execute($opened['plainToken']);

            return $this->anonymizeUser->execute($confirmed);
        });

        return redirect()
            ->route('admin.data-lifecycle.deletion-requests.show', $deletionRequest)
            ->with('status', 'User has been anonymized immediately (email confirmation and grace period skipped).');
    }
}
