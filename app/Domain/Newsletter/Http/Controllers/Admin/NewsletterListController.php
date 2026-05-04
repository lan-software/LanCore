<?php

namespace App\Domain\Newsletter\Http\Controllers\Admin;

use App\Domain\Newsletter\Actions\CreateNewsletterList;
use App\Domain\Newsletter\Actions\DeleteNewsletterList;
use App\Domain\Newsletter\Actions\UpdateNewsletterList;
use App\Domain\Newsletter\Http\Requests\Admin\StoreNewsletterListRequest;
use App\Domain\Newsletter\Http\Requests\Admin\UpdateNewsletterListRequest;
use App\Domain\Newsletter\Models\NewsletterList;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @see docs/mil-std-498/SSS.md CAP-NLT-001
 * @see docs/mil-std-498/SRS.md NLT-F-001
 */
class NewsletterListController extends Controller
{
    public function __construct(
        private readonly CreateNewsletterList $createList,
        private readonly UpdateNewsletterList $updateList,
        private readonly DeleteNewsletterList $deleteList,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', NewsletterList::class);

        return Inertia::render('newsletter-lists/Index', [
            'lists' => NewsletterList::query()
                ->orderBy('name')
                ->get([
                    'id',
                    'listmonk_id',
                    'name',
                    'description',
                    'type',
                    'optin',
                    'tags',
                    'is_user_selectable',
                    'is_default_public',
                    'last_synced_at',
                ]),
            'listmonkEnabled' => (bool) config('listmonk.enabled'),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', NewsletterList::class);

        return Inertia::render('newsletter-lists/Create');
    }

    public function store(StoreNewsletterListRequest $request): RedirectResponse
    {
        $this->authorize('create', NewsletterList::class);

        $this->createList->execute($request->validated());

        return redirect()->route('newsletter-lists.index');
    }

    public function edit(NewsletterList $newsletterList): Response
    {
        $this->authorize('update', $newsletterList);

        return Inertia::render('newsletter-lists/Edit', [
            'list' => $newsletterList,
        ]);
    }

    public function update(
        UpdateNewsletterListRequest $request,
        NewsletterList $newsletterList,
    ): RedirectResponse {
        $this->authorize('update', $newsletterList);

        $this->updateList->execute($newsletterList, $request->validated());

        return redirect()->route('newsletter-lists.index');
    }

    public function destroy(NewsletterList $newsletterList): RedirectResponse
    {
        $this->authorize('delete', $newsletterList);

        $this->deleteList->execute($newsletterList);

        return redirect()->route('newsletter-lists.index');
    }
}
