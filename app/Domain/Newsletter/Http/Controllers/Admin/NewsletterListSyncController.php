<?php

namespace App\Domain\Newsletter\Http\Controllers\Admin;

use App\Domain\Newsletter\Actions\FetchListsFromListmonk;
use App\Domain\Newsletter\Actions\OptInAllUsersToList;
use App\Domain\Newsletter\Models\NewsletterList;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

/**
 * @see docs/mil-std-498/SSS.md CAP-NLT-001, CAP-NLT-004
 * @see docs/mil-std-498/SRS.md NLT-F-002, NLT-F-005
 */
class NewsletterListSyncController extends Controller
{
    public function __construct(
        private readonly FetchListsFromListmonk $fetchLists,
        private readonly OptInAllUsersToList $optInAll,
    ) {}

    public function fetch(): RedirectResponse
    {
        $this->authorize('create', NewsletterList::class);

        $stats = $this->fetchLists->execute();

        return back()->with(
            'status',
            __('Listmonk lists synced: :created created, :updated updated, :removed removed.', $stats),
        );
    }

    public function optInAllUsers(NewsletterList $newsletterList): RedirectResponse
    {
        $this->authorize('update', $newsletterList);

        $count = $this->optInAll->execute($newsletterList);

        return back()->with(
            'status',
            __("Queued :count opt-in jobs for ':name'.", [
                'count' => $count,
                'name' => $newsletterList->name,
            ]),
        );
    }
}
