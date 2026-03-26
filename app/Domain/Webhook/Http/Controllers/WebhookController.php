<?php

namespace App\Domain\Webhook\Http\Controllers;

use App\Domain\Webhook\Actions\CreateWebhook;
use App\Domain\Webhook\Actions\DeleteWebhook;
use App\Domain\Webhook\Actions\UpdateWebhook;
use App\Domain\Webhook\Enums\WebhookEvent;
use App\Domain\Webhook\Http\Requests\StoreWebhookRequest;
use App\Domain\Webhook\Http\Requests\UpdateWebhookRequest;
use App\Domain\Webhook\Models\Webhook;
use App\Domain\Webhook\Models\WebhookDelivery;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WebhookController extends Controller
{
    public function __construct(
        private readonly CreateWebhook $createWebhook,
        private readonly UpdateWebhook $updateWebhook,
        private readonly DeleteWebhook $deleteWebhook,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Webhook::class);

        $query = Webhook::query();

        if ($search = $request->input('search')) {
            $query->where('name', 'ilike', "%{$search}%")
                ->orWhere('url', 'ilike', "%{$search}%");
        }

        $sortColumn = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');
        $query->orderBy($sortColumn, $sortDirection);

        $query->addSelect([
            'last_delivery_status_code' => WebhookDelivery::select('status_code')
                ->whereColumn('webhook_id', 'webhooks.id')
                ->orderByDesc('fired_at')
                ->limit(1),
        ]);

        $webhooks = $query->withCount('deliveries')
            ->with('integrationApp:id,name,slug')
            ->paginate($request->input('per_page', 20))->withQueryString();

        return Inertia::render('webhooks/Index', [
            'webhooks' => $webhooks,
            'filters' => $request->only(['search', 'sort', 'direction', 'per_page']),
        ]);
    }

    public function show(Request $request, Webhook $webhook): Response
    {
        $this->authorize('view', $webhook);

        $webhook->load('integrationApp:id,name,slug');

        $deliveries = $webhook->deliveries()
            ->paginate($request->input('per_page', 25))
            ->withQueryString();

        return Inertia::render('webhooks/Show', [
            'webhook' => $webhook,
            'deliveries' => $deliveries,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Webhook::class);

        return Inertia::render('webhooks/Create', [
            'events' => collect(WebhookEvent::cases())->map(fn (WebhookEvent $e) => [
                'value' => $e->value,
                'label' => $e->label(),
            ]),
        ]);
    }

    public function store(StoreWebhookRequest $request): RedirectResponse
    {
        $this->authorize('create', Webhook::class);

        $this->createWebhook->execute($request->safe()->all());

        return redirect()->route('webhooks.index');
    }

    public function edit(Webhook $webhook): Response
    {
        $this->authorize('update', $webhook);

        return Inertia::render('webhooks/Edit', [
            'webhook' => $webhook,
            'events' => collect(WebhookEvent::cases())->map(fn (WebhookEvent $e) => [
                'value' => $e->value,
                'label' => $e->label(),
            ]),
        ]);
    }

    public function update(UpdateWebhookRequest $request, Webhook $webhook): RedirectResponse
    {
        $this->authorize('update', $webhook);

        $this->updateWebhook->execute($webhook, $request->safe()->all());

        return back();
    }

    public function destroy(Webhook $webhook): RedirectResponse
    {
        $this->authorize('delete', $webhook);

        $this->deleteWebhook->execute($webhook);

        return redirect()->route('webhooks.index');
    }
}
