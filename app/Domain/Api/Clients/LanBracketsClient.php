<?php

namespace App\Domain\Api\Clients;

use App\Domain\Competition\Exceptions\LanBracketsDisabledException;
use App\Domain\Competition\Exceptions\LanBracketsRequestException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * HTTP client for LanBrackets API v1.
 *
 * @see docs/mil-std-498/IDD.md COMP-INT-001
 */
class LanBracketsClient
{
    /**
     * @param  array{name: string, type: string, stage_type: string, description?: string, settings?: array<string, mixed>, external_reference_id?: string, source_system?: string, metadata?: array<string, mixed>}  $data
     * @return array<string, mixed>
     *
     * @throws LanBracketsDisabledException
     * @throws LanBracketsRequestException
     */
    public function createCompetition(array $data): array
    {
        $this->ensureEnabled();

        $response = $this->withRetries(
            fn () => $this->apiClient()->post('/api/v1/competitions', $data)
        );

        if (! $response->successful()) {
            throw new LanBracketsRequestException(
                $response->json('message') ?? 'Failed to create competition.',
                $response->status()
            );
        }

        return $response->json('data', []);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws LanBracketsDisabledException
     * @throws LanBracketsRequestException
     */
    public function updateCompetition(string $id, array $data): array
    {
        $this->ensureEnabled();

        $response = $this->withRetries(
            fn () => $this->apiClient()->put("/api/v1/competitions/{$id}", $data)
        );

        if (! $response->successful()) {
            throw new LanBracketsRequestException(
                $response->json('message') ?? 'Failed to update competition.',
                $response->status()
            );
        }

        return $response->json('data', []);
    }

    /**
     * @throws LanBracketsDisabledException
     * @throws LanBracketsRequestException
     */
    public function deleteCompetition(string $id): void
    {
        $this->ensureEnabled();

        $response = $this->withRetries(
            fn () => $this->apiClient()->delete("/api/v1/competitions/{$id}")
        );

        if (! $response->successful() && $response->status() !== 204) {
            throw new LanBracketsRequestException(
                $response->json('message') ?? 'Failed to delete competition.',
                $response->status()
            );
        }
    }

    /**
     * @return array<string, mixed>
     *
     * @throws LanBracketsDisabledException
     * @throws LanBracketsRequestException
     */
    public function getCompetition(string $id): array
    {
        $this->ensureEnabled();

        $response = $this->withRetries(
            fn () => $this->apiClient()->get("/api/v1/competitions/{$id}")
        );

        if (! $response->successful()) {
            throw new LanBracketsRequestException(
                $response->json('message') ?? 'Failed to get competition.',
                $response->status()
            );
        }

        return $response->json('data', []);
    }

    /**
     * @return array<string, mixed>|null
     *
     * @throws LanBracketsDisabledException
     * @throws LanBracketsRequestException
     */
    public function findByExternalReference(string $externalId, string $sourceSystem = 'lancore'): ?array
    {
        $this->ensureEnabled();

        $response = $this->withRetries(
            fn () => $this->apiClient()->get('/api/v1/competitions', [
                'external_reference_id' => $externalId,
                'source_system' => $sourceSystem,
            ])
        );

        if (! $response->successful()) {
            throw new LanBracketsRequestException(
                $response->json('message') ?? 'Failed to find competition.',
                $response->status()
            );
        }

        $data = $response->json('data', []);

        return $data[0] ?? null;
    }

    /**
     * Upsert a team on LanBrackets keyed on `(source_system, external_reference_id)`.
     *
     * Called per `CompetitionTeam` before the bulk participant call so that
     * participants reference the canonical LanBrackets team id, not whatever
     * collides with the local CompetitionTeam id.
     *
     * @param  array{name: string, tag?: string|null, description?: string|null, external_reference_id: string, source_system?: string, status?: string}  $data
     * @return array<string, mixed>
     *
     * @throws LanBracketsDisabledException
     * @throws LanBracketsRequestException
     *
     * @see docs/mil-std-498/SRS.md COMP-F-018
     * @see docs/mil-std-498/IDD.md §3.8.1
     */
    public function upsertTeam(array $data): array
    {
        $this->ensureEnabled();

        $data['source_system'] = $data['source_system'] ?? 'lancore';

        $response = $this->withRetries(
            fn () => $this->apiClient()->post('/api/v1/teams', $data)
        );

        if (! $response->successful()) {
            throw new LanBracketsRequestException(
                $response->json('message') ?? 'Failed to upsert team.',
                $response->status()
            );
        }

        return $response->json('data', $response->json() ?? []);
    }

    /**
     * @param  array{participant_type: string, participant_id: string, seed?: int|null}  $data
     * @return array<string, mixed>
     *
     * @throws LanBracketsDisabledException
     * @throws LanBracketsRequestException
     */
    public function addParticipant(string $competitionId, array $data): array
    {
        $this->ensureEnabled();

        $response = $this->withRetries(
            fn () => $this->apiClient()->post("/api/v1/competitions/{$competitionId}/participants", $data)
        );

        if (! $response->successful()) {
            throw new LanBracketsRequestException(
                $response->json('message') ?? 'Failed to add participant.',
                $response->status()
            );
        }

        return $response->json('data', []);
    }

    /**
     * @param  array<int, array{participant_type: string, participant_id: string, seed?: int|null}>  $participants
     * @return array<string, mixed>
     *
     * @throws LanBracketsDisabledException
     * @throws LanBracketsRequestException
     */
    public function bulkAddParticipants(string $competitionId, array $participants): array
    {
        $this->ensureEnabled();

        $response = $this->withRetries(
            fn () => $this->apiClient()->post("/api/v1/competitions/{$competitionId}/participants/bulk", [
                'participants' => $participants,
            ])
        );

        if (! $response->successful()) {
            throw new LanBracketsRequestException(
                $response->json('message') ?? 'Failed to bulk add participants.',
                $response->status()
            );
        }

        return $response->json('data', []);
    }

    /**
     * @throws LanBracketsDisabledException
     * @throws LanBracketsRequestException
     */
    public function withdrawParticipant(string $competitionId, string $participantId): void
    {
        $this->ensureEnabled();

        $response = $this->withRetries(
            fn () => $this->apiClient()->delete("/api/v1/competitions/{$competitionId}/participants/{$participantId}")
        );

        if (! $response->successful() && $response->status() !== 204) {
            throw new LanBracketsRequestException(
                $response->json('message') ?? 'Failed to withdraw participant.',
                $response->status()
            );
        }
    }

    /**
     * @param  array<int, array{participant_id: string, score: int}>  $scores
     * @return array<string, mixed>
     *
     * @throws LanBracketsDisabledException
     * @throws LanBracketsRequestException
     */
    public function reportMatchResult(string $competitionId, string $matchId, array $scores): array
    {
        $this->ensureEnabled();

        $response = $this->withRetries(
            fn () => $this->apiClient()->post("/api/v1/competitions/{$competitionId}/matches/{$matchId}/result", [
                'scores' => $scores,
            ])
        );

        if (! $response->successful()) {
            throw new LanBracketsRequestException(
                $response->json('message') ?? 'Failed to report match result.',
                $response->status()
            );
        }

        return $response->json('data', []);
    }

    /**
     * @return array<int, array<string, mixed>>
     *
     * @throws LanBracketsDisabledException
     * @throws LanBracketsRequestException
     */
    public function getStages(string $competitionId): array
    {
        $this->ensureEnabled();

        $response = $this->withRetries(
            fn () => $this->apiClient()->get("/api/v1/competitions/{$competitionId}/stages")
        );

        if (! $response->successful()) {
            throw new LanBracketsRequestException(
                $response->json('message') ?? 'Failed to get stages.',
                $response->status()
            );
        }

        return $response->json('data', []);
    }

    /**
     * Trigger LanBrackets to generate the bracket / matches for a stage.
     * LanBrackets does not auto-generate matches when participants are added;
     * the consumer (LanCore) calls this after registration closes and teams
     * have been synced.
     *
     * @return array<string, mixed>
     *
     * @throws LanBracketsDisabledException
     * @throws LanBracketsRequestException
     */
    public function generateStage(string $competitionId, string $stageId): array
    {
        $this->ensureEnabled();

        $response = $this->withRetries(
            fn () => $this->apiClient()->post("/api/v1/competitions/{$competitionId}/stages/{$stageId}/generate")
        );

        if (! $response->successful()) {
            throw new LanBracketsRequestException(
                $response->json('message') ?? 'Failed to generate stage.',
                $response->status()
            );
        }

        return $response->json('data', $response->json());
    }

    /**
     * @return array<int, array<string, mixed>>
     *
     * @throws LanBracketsDisabledException
     * @throws LanBracketsRequestException
     */
    public function getMatches(string $competitionId, string $stageId): array
    {
        $this->ensureEnabled();

        $response = $this->withRetries(
            fn () => $this->apiClient()->get("/api/v1/competitions/{$competitionId}/stages/{$stageId}/matches")
        );

        if (! $response->successful()) {
            throw new LanBracketsRequestException(
                $response->json('message') ?? 'Failed to get matches.',
                $response->status()
            );
        }

        return $response->json('data', []);
    }

    /**
     * @return array<int, array<string, mixed>>
     *
     * @throws LanBracketsDisabledException
     * @throws LanBracketsRequestException
     */
    public function getStandings(string $competitionId): array
    {
        $this->ensureEnabled();

        $response = $this->withRetries(
            fn () => $this->apiClient()->get("/api/v1/competitions/{$competitionId}/standings")
        );

        if (! $response->successful()) {
            throw new LanBracketsRequestException(
                $response->json('message') ?? 'Failed to get standings.',
                $response->status()
            );
        }

        return $response->json('data', []);
    }

    /**
     * @throws LanBracketsDisabledException
     * @throws LanBracketsRequestException
     */
    public function regenerateShareToken(string $competitionId): string
    {
        $this->ensureEnabled();

        $response = $this->withRetries(
            fn () => $this->apiClient()->post("/api/v1/competitions/{$competitionId}/share-token")
        );

        if (! $response->successful()) {
            throw new LanBracketsRequestException(
                $response->json('message') ?? 'Failed to regenerate share token.',
                $response->status()
            );
        }

        return $response->json('share_token', '');
    }

    /**
     * @throws LanBracketsRequestException
     */
    private function withRetries(callable $callback): Response
    {
        $retries = config('lanbrackets.retries', 2);
        $delay = config('lanbrackets.retry_delay', 100);
        $attempt = 0;

        while (true) {
            try {
                return $callback();
            } catch (ConnectionException $e) {
                if ($attempt >= $retries) {
                    throw new LanBracketsRequestException('LanBrackets is unreachable: '.$e->getMessage());
                }
                $attempt++;
                usleep($delay * 1000);
            }
        }
    }

    private function apiClient(): PendingRequest
    {
        $baseUrl = config('lanbrackets.internal_url') ?? config('lanbrackets.base_url');

        return Http::baseUrl(rtrim($baseUrl, '/'))
            ->timeout(config('lanbrackets.timeout', 5))
            ->withToken(config('lanbrackets.token'))
            ->acceptJson();
    }

    /**
     * @throws LanBracketsDisabledException
     */
    private function ensureEnabled(): void
    {
        if (! config('lanbrackets.enabled')) {
            throw new LanBracketsDisabledException;
        }
    }
}
