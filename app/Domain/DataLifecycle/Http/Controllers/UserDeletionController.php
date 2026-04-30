<?php

namespace App\Domain\DataLifecycle\Http\Controllers;

use App\Domain\DataLifecycle\Actions\CancelUserDeletion;
use App\Domain\DataLifecycle\Actions\ConfirmUserDeletion;
use App\Domain\DataLifecycle\Actions\RequestUserDeletion;
use App\Domain\DataLifecycle\Enums\DeletionInitiator;
use App\Domain\DataLifecycle\Enums\DeletionRequestStatus;
use App\Domain\DataLifecycle\Http\Requests\RequestSelfDeletionRequest;
use App\Domain\DataLifecycle\Models\DeletionRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * User-facing self-service deletion flow.
 *
 * @see docs/mil-std-498/SSS.md CAP-DL-001, CAP-DL-003
 * @see docs/mil-std-498/SRS.md DL-F-001, DL-F-003, DL-F-004
 */
class UserDeletionController extends Controller
{
    public function __construct(
        private readonly RequestUserDeletion $requestUserDeletion,
        private readonly ConfirmUserDeletion $confirmUserDeletion,
        private readonly CancelUserDeletion $cancelUserDeletion,
    ) {}

    public function show(Request $request): Response
    {
        $user = $request->user();

        $current = DeletionRequest::query()
            ->where('user_id', $user->getKey())
            ->whereIn('status', [
                DeletionRequestStatus::PendingEmailConfirm->value,
                DeletionRequestStatus::PendingGrace->value,
            ])
            ->latest('id')
            ->first();

        return Inertia::render('account/Delete', [
            'pendingRequest' => $current === null ? null : [
                'id' => $current->getKey(),
                'status' => $current->status->value,
                'scheduled_for' => $current->scheduled_for?->toIso8601String(),
                'email_confirmed_at' => $current->email_confirmed_at?->toIso8601String(),
            ],
        ]);
    }

    public function request(RequestSelfDeletionRequest $request): RedirectResponse
    {
        $user = $request->user();

        $this->requestUserDeletion->execute(
            subject: $user,
            initiator: DeletionInitiator::User,
            reason: $request->validated('reason'),
        );

        return redirect()
            ->route('data-lifecycle.account.show')
            ->with('status', 'A confirmation email has been sent. Click the link to start the 30-day grace period.');
    }

    public function confirm(string $token): Response
    {
        try {
            $deletionRequest = $this->confirmUserDeletion->execute($token);
        } catch (\Throwable) {
            return Inertia::render('account/Delete', [
                'flash' => [
                    'level' => 'error',
                    'message' => 'This deletion confirmation link is invalid or has already been used.',
                ],
            ]);
        }

        return Inertia::render('account/DeletionPending', [
            'deletionRequest' => [
                'id' => $deletionRequest->getKey(),
                'status' => $deletionRequest->status->value,
                'scheduled_for' => $deletionRequest->scheduled_for?->toIso8601String(),
            ],
        ]);
    }

    public function cancel(DeletionRequest $request, Request $httpRequest): RedirectResponse
    {
        $this->authorize('cancel', $request);

        if ($request->user_id !== $httpRequest->user()->getKey()) {
            abort(403);
        }

        $this->cancelUserDeletion->execute($request);

        return redirect()
            ->route('data-lifecycle.account.show')
            ->with('status', 'Account deletion has been cancelled. Your account is fully active again.');
    }

    public function cancelViaLink(DeletionRequest $request): RedirectResponse
    {
        $this->cancelUserDeletion->execute($request);

        return redirect()
            ->route('login')
            ->with('status', 'Account deletion has been cancelled. Please log in to continue using your account.');
    }
}
