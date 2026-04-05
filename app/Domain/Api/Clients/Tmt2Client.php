<?php

namespace App\Domain\Api\Clients;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * HTTP client for the TMT2 (Tournament Match Tracker 2) REST API.
 *
 * TMT2 manages CS2 match supervision: map veto/pick, knife rounds,
 * side selection, live score tracking, and RCON communication.
 *
 * @see docs/tmt2-backend.json OpenAPI 3.0 specification
 */
class Tmt2Client
{
    /**
     * Create a new match on TMT2.
     *
     * @param  array{mapPool: array<string>, teamA: array<string, mixed>, teamB: array<string, mixed>, electionSteps: array<array<string, mixed>>, gameServer: array{ip: string, port: int, rconPassword: string}|null, webhookUrl?: string|null, webhookHeaders?: array<string, string>|null, rconCommands?: array{init?: array<string>, knife?: array<string>, match?: array<string>, end?: array<string>}, canClinch?: bool, matchEndAction?: string, mode?: string}  $data
     * @return array<string, mixed>
     */
    public function createMatch(array $data): array
    {
        $this->ensureEnabled();

        $response = $this->withRetries(
            fn () => $this->apiClient()->post('/api/matches', $data)
        );

        if (! $response->successful()) {
            throw new RuntimeException(
                'TMT2: Failed to create match: '.($response->body()),
                $response->status()
            );
        }

        return $response->json();
    }

    /**
     * Get all matches, optionally filtered.
     *
     * @param  array{state?: array<string>, passthrough?: array<string>, isStopped?: bool, isLive?: bool}  $filters
     * @return array<int, array<string, mixed>>
     */
    public function getMatches(array $filters = []): array
    {
        $this->ensureEnabled();

        $response = $this->withRetries(
            fn () => $this->apiClient()->get('/api/matches', $filters)
        );

        if (! $response->successful()) {
            throw new RuntimeException(
                'TMT2: Failed to get matches: '.($response->body()),
                $response->status()
            );
        }

        return $response->json();
    }

    /**
     * Get a specific match by its TMT2 ID.
     *
     * @return array<string, mixed>|null
     */
    public function getMatch(string $id): ?array
    {
        $this->ensureEnabled();

        $response = $this->withRetries(
            fn () => $this->apiClient()->get("/api/matches/{$id}")
        );

        if (! $response->successful()) {
            return null;
        }

        return $response->json();
    }

    /**
     * Update a specific match.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateMatch(string $id, array $data): void
    {
        $this->ensureEnabled();

        $response = $this->withRetries(
            fn () => $this->apiClient()->patch("/api/matches/{$id}", $data)
        );

        if (! $response->successful()) {
            throw new RuntimeException(
                'TMT2: Failed to update match: '.($response->body()),
                $response->status()
            );
        }
    }

    /**
     * Stop supervising a match (executes end rcon commands).
     */
    public function deleteMatch(string $id): void
    {
        $this->ensureEnabled();

        $response = $this->withRetries(
            fn () => $this->apiClient()->delete("/api/matches/{$id}")
        );

        if (! $response->successful()) {
            throw new RuntimeException(
                'TMT2: Failed to delete match: '.($response->body()),
                $response->status()
            );
        }
    }

    /**
     * Validate that the TMT2 API is reachable and the token is valid.
     */
    public function login(): bool
    {
        $this->ensureEnabled();

        try {
            $response = $this->withRetries(
                fn () => $this->apiClient()->post('/api/login')
            );

            return $response->status() === 204;
        } catch (RuntimeException) {
            return false;
        }
    }

    private function withRetries(callable $callback): Response
    {
        $retries = config('tmt2.retries', 2);
        $delay = config('tmt2.retry_delay', 100);
        $attempt = 0;

        while (true) {
            try {
                return $callback();
            } catch (ConnectionException $e) {
                if ($attempt >= $retries) {
                    throw new RuntimeException('TMT2 is unreachable: '.$e->getMessage());
                }
                $attempt++;
                usleep($delay * 1000);
            }
        }
    }

    private function apiClient(): PendingRequest
    {
        return Http::baseUrl(rtrim(config('tmt2.base_url'), '/'))
            ->timeout(config('tmt2.timeout', 5))
            ->withToken(config('tmt2.token'))
            ->acceptJson();
    }

    private function ensureEnabled(): void
    {
        if (! config('tmt2.enabled')) {
            throw new RuntimeException('TMT2 integration is not enabled.');
        }
    }
}
