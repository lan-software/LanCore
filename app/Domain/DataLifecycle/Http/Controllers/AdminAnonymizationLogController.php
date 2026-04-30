<?php

namespace App\Domain\DataLifecycle\Http\Controllers;

use App\Domain\DataLifecycle\Enums\Permission;
use App\Domain\DataLifecycle\Models\AnonymizationLogEntry;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Read-only viewer for the append-only anonymization log. Lets compliance
 * officers and admins inspect the paper trail of every per-domain scrub.
 *
 * @see docs/mil-std-498/SRS.md DL-F-010, DL-F-013
 */
class AdminAnonymizationLogController extends Controller
{
    public function index(Request $request): Response
    {
        if (! $request->user()->hasPermission(Permission::ViewDeletionRequests)) {
            abort(403);
        }

        $query = AnonymizationLogEntry::query()
            ->with(['user' => fn ($q) => $q->withTrashed()])
            ->orderByDesc('id');

        if ($userId = $request->integer('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($dataClass = $request->string('data_class')->toString()) {
            if ($dataClass !== '') {
                $query->where('data_class', $dataClass);
            }
        }

        $entries = $query->paginate(50)->withQueryString();

        return Inertia::render('admin/data-lifecycle/AnonymizationLog/Index', [
            'entries' => $entries,
            'filters' => $request->only(['user_id', 'data_class']),
        ]);
    }
}
