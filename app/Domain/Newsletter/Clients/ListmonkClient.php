<?php

namespace App\Domain\Newsletter\Clients;

use App\Domain\Newsletter\Exceptions\ListmonkException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Thin Laravel `Http` wrapper for the Listmonk REST API.
 *
 * Modeled after `app/Domain/Api/Clients/Tmt2Client.php` for consistency:
 * fluent client construction, `withRetries()` helper, typed methods that
 * return decoded arrays. All non-2xx responses raise `ListmonkException`
 * with a `$kind` discriminator the External-API connectivity test
 * endpoints map back to their standard JSON contract.
 *
 * @see https://listmonk.app/docs/apis/
 * @see docs/mil-std-498/SDD.md §5.12
 */
class ListmonkClient
{
    /**
     * Probe Listmonk's `/api/health` endpoint. Returns the raw `data`
     * payload (typically a build/version string).
     *
     * @return array<string, mixed>
     */
    public function health(): array
    {
        $this->ensureConfigured();

        $response = $this->withRetries(
            fn () => $this->client()->get('/api/health')
        );

        return $this->decode($response, 'health check');
    }

    /**
     * List Listmonk lists. Pagination is via Listmonk's `page` + `per_page`
     * query parameters; default is the largest sensible page so callers
     * usually only need one round-trip.
     *
     * @return array{results: array<int, array<string, mixed>>, total: int, page: int, per_page: int}
     */
    public function listLists(int $page = 1, int $perPage = 100): array
    {
        $this->ensureConfigured();

        $response = $this->withRetries(
            fn () => $this->client()->get('/api/lists', [
                'page' => $page,
                'per_page' => $perPage,
            ])
        );

        return $this->decode($response, 'list lists');
    }

    /**
     * Fetch a single Listmonk list by id.
     *
     * @return array<string, mixed>
     */
    public function getList(string $id): array
    {
        $this->ensureConfigured();

        $response = $this->withRetries(
            fn () => $this->client()->get("/api/lists/{$id}")
        );

        return $this->decode($response, "get list {$id}");
    }

    /**
     * Create a new list inside Listmonk.
     *
     * @param  array{name: string, type?: string, optin?: string, tags?: array<int, string>, description?: string}  $payload
     * @return array<string, mixed>
     */
    public function createList(array $payload): array
    {
        $this->ensureConfigured();

        $response = $this->withRetries(
            fn () => $this->client()->post('/api/lists', $payload)
        );

        return $this->decode($response, 'create list');
    }

    /**
     * Update an existing Listmonk list.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function updateList(string $id, array $payload): array
    {
        $this->ensureConfigured();

        $response = $this->withRetries(
            fn () => $this->client()->put("/api/lists/{$id}", $payload)
        );

        return $this->decode($response, "update list {$id}");
    }

    /**
     * Delete a Listmonk list.
     */
    public function deleteList(string $id): void
    {
        $this->ensureConfigured();

        $response = $this->withRetries(
            fn () => $this->client()->delete("/api/lists/{$id}")
        );

        $this->decode($response, "delete list {$id}");
    }

    /**
     * Look up a subscriber by email.
     *
     * @return array<string, mixed>|null
     */
    public function getSubscriberByEmail(string $email): ?array
    {
        $this->ensureConfigured();

        $response = $this->withRetries(
            fn () => $this->client()->get('/api/subscribers', [
                'query' => "subscribers.email = '".addslashes($email)."'",
                'per_page' => 1,
            ])
        );

        $data = $this->decode($response, 'lookup subscriber');
        $results = $data['results'] ?? [];

        return $results === [] ? null : $results[0];
    }

    /**
     * Upsert a subscriber, then add them to one or more lists. If
     * `$preconfirm` is true, the subscriber is set to `enabled` directly
     * (bypassing the confirmation email).
     *
     * @param  array<int, int>  $listIds
     * @return array<string, mixed>
     */
    public function upsertSubscriber(string $email, ?string $name, array $listIds, bool $preconfirm = false): array
    {
        $this->ensureConfigured();

        $existing = $this->getSubscriberByEmail($email);

        if ($existing !== null) {
            $merged = array_values(array_unique(array_merge(
                array_map(static fn (array $l): int => (int) $l['id'], $existing['lists'] ?? []),
                $listIds,
            )));

            return $this->updateSubscriber((int) $existing['id'], [
                'email' => $email,
                'name' => $name ?? ($existing['name'] ?? $email),
                'status' => $preconfirm ? 'enabled' : ($existing['status'] ?? 'enabled'),
                'lists' => $merged,
                'preconfirm_subscriptions' => $preconfirm,
            ]);
        }

        $response = $this->withRetries(
            fn () => $this->client()->post('/api/subscribers', [
                'email' => $email,
                'name' => $name ?? $email,
                'status' => 'enabled',
                'lists' => $listIds,
                'preconfirm_subscriptions' => $preconfirm,
            ])
        );

        return $this->decode($response, 'create subscriber');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function updateSubscriber(string $id, array $payload): array
    {
        $this->ensureConfigured();

        $response = $this->withRetries(
            fn () => $this->client()->put("/api/subscribers/{$id}", $payload)
        );

        return $this->decode($response, "update subscriber {$id}");
    }

    /**
     * Manage list memberships for a single subscriber. Listmonk's bulk
     * endpoint takes `ids` (subscribers), `action` (`add`/`remove`/`unsubscribe`),
     * `target_list_ids`, and an optional `status`.
     *
     * @param  array<int, int>  $listIds
     */
    public function manageSubscriberLists(string $subscriberId, array $listIds, string $action, ?string $status = null): void
    {
        $this->ensureConfigured();

        $payload = [
            'ids' => [$subscriberId],
            'action' => $action,
            'target_list_ids' => $listIds,
        ];

        if ($status !== null) {
            $payload['status'] = $status;
        }

        $response = $this->withRetries(
            fn () => $this->client()->put('/api/subscribers/lists', $payload)
        );

        $this->decode($response, "manage subscriber {$subscriberId} list memberships");
    }

    /**
     * Page through subscribers belonging to a specific list. Used by the
     * nightly `ReconcileListSubscriptionsJob`.
     *
     * @return array{results: array<int, array<string, mixed>>, total: int, page: int, per_page: int}
     */
    public function getSubscribersOfList(string $listId, int $page = 1, int $perPage = 100): array
    {
        $this->ensureConfigured();

        $response = $this->withRetries(
            fn () => $this->client()->get('/api/subscribers', [
                'list_id' => [$listId],
                'page' => $page,
                'per_page' => $perPage,
            ])
        );

        return $this->decode($response, "list subscribers of list {$listId}");
    }

    private function client(bool $authenticated = true): PendingRequest
    {
        $client = Http::baseUrl(rtrim((string) config('listmonk.base_url'), '/'))
            ->timeout((int) config('listmonk.timeout', 10))
            ->acceptJson();

        if ($authenticated) {
            $client = $client->withBasicAuth(
                (string) config('listmonk.username'),
                (string) config('listmonk.password'),
            );
        }

        return $client;
    }

    private function withRetries(callable $callback): Response
    {
        $retries = (int) config('listmonk.retries', 2);
        $delay = (int) config('listmonk.retry_delay', 200);
        $attempt = 0;

        while (true) {
            try {
                return $callback();
            } catch (ConnectionException $e) {
                if ($attempt >= $retries) {
                    throw new ListmonkException(
                        'Listmonk is unreachable: '.$e->getMessage(),
                        ListmonkException::KIND_UNREACHABLE,
                        previous: $e,
                    );
                }

                $attempt++;
                usleep($delay * 1000);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(Response $response, string $context): array
    {
        if ($response->status() === 401 || $response->status() === 403) {
            throw new ListmonkException(
                "Listmonk authentication failed during {$context} (HTTP {$response->status()}).",
                ListmonkException::KIND_AUTH_FAILED,
                $response->status(),
            );
        }

        if (! $response->successful()) {
            throw new ListmonkException(
                "Listmonk {$context} failed: HTTP {$response->status()} {$response->body()}",
                ListmonkException::KIND_API_ERROR,
                $response->status(),
            );
        }

        $payload = $response->json();
        $data = is_array($payload) && array_key_exists('data', $payload) ? $payload['data'] : $payload;

        return is_array($data) ? $data : [];
    }

    private function ensureConfigured(): void
    {
        if (! config('listmonk.enabled')) {
            throw new ListmonkException(
                'Listmonk integration is not enabled. Set LISTMONK_ENABLED=true and configure LISTMONK_BASE_URL/USERNAME/PASSWORD.',
                ListmonkException::KIND_NOT_CONFIGURED,
            );
        }

        if (! config('listmonk.base_url') || ! config('listmonk.username') || ! config('listmonk.password')) {
            throw new ListmonkException(
                'Listmonk integration is missing required configuration (base URL, username, or password).',
                ListmonkException::KIND_NOT_CONFIGURED,
            );
        }
    }
}
