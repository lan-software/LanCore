<?php

namespace App\Domain\Integration\Http\Controllers;

use App\Domain\Integration\Actions\CreateIntegrationApp;
use App\Domain\Integration\Actions\DeleteIntegrationApp;
use App\Domain\Integration\Actions\UpdateIntegrationApp;
use App\Domain\Integration\Http\Requests\IntegrationAppIndexRequest;
use App\Domain\Integration\Http\Requests\StoreIntegrationAppRequest;
use App\Domain\Integration\Http\Requests\UpdateIntegrationAppRequest;
use App\Domain\Integration\Models\IntegrationApp;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class IntegrationAppController extends Controller
{
    public function __construct(
        private readonly CreateIntegrationApp $createIntegrationApp,
        private readonly UpdateIntegrationApp $updateIntegrationApp,
        private readonly DeleteIntegrationApp $deleteIntegrationApp,
    ) {}

    public function index(IntegrationAppIndexRequest $request): Response
    {
        $this->authorize('viewAny', IntegrationApp::class);

        $query = IntegrationApp::withCount('tokens', 'activeTokens');

        if ($search = $request->validated('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('slug', 'ilike', "%{$search}%");
            });
        }

        $sortColumn = $request->validated('sort') ?? 'created_at';
        $sortDirection = $request->validated('direction') ?? 'desc';
        $query->orderBy($sortColumn, $sortDirection);

        $apps = $query->paginate($request->validated('per_page') ?? 20)->withQueryString();

        return Inertia::render('integrations/Index', [
            'integrationApps' => $apps,
            'filters' => $request->only(['search', 'sort', 'direction', 'per_page']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', IntegrationApp::class);

        return Inertia::render('integrations/Create', [
            'availableScopes' => self::availableScopes(),
        ]);
    }

    public function store(StoreIntegrationAppRequest $request): RedirectResponse
    {
        $this->authorize('create', IntegrationApp::class);

        $this->createIntegrationApp->execute($request->validated());

        return redirect()->route('integrations.index');
    }

    public function edit(IntegrationApp $integration): Response
    {
        $this->authorize('update', $integration);

        $integration->load(['tokens' => fn ($q) => $q->orderByDesc('created_at')]);

        return Inertia::render('integrations/Edit', [
            'integrationApp' => $integration,
            'availableScopes' => self::availableScopes(),
        ]);
    }

    public function update(UpdateIntegrationAppRequest $request, IntegrationApp $integration): RedirectResponse
    {
        $this->authorize('update', $integration);

        $this->updateIntegrationApp->execute($integration, $request->validated());

        return back();
    }

    public function destroy(IntegrationApp $integration): RedirectResponse
    {
        $this->authorize('delete', $integration);

        $this->deleteIntegrationApp->execute($integration);

        return redirect()->route('integrations.index');
    }

    /**
     * @return array<array{value: string, label: string, description: string}>
     */
    private static function availableScopes(): array
    {
        return [
            ['value' => 'user:read', 'label' => 'Read User Profile', 'description' => 'Access basic user information (id, username)'],
            ['value' => 'user:email', 'label' => 'Read User Email', 'description' => 'Access the user\'s email address'],
            ['value' => 'user:roles', 'label' => 'Read User Roles', 'description' => 'Access the user\'s assigned roles'],
        ];
    }
}
