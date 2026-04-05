<?php

namespace App\Domain\Competition\Services;

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
    public function updateCompetition(int $id, array $data): array
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
    public function deleteCompetition(int $id): void
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
    public function getCompetition(int $id): array
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
     * @param  array{participant_type: string, participant_id: int, seed?: int|null}  $data
     * @return array<string, mixed>
     *
     * @throws LanBracketsDisabledException
     * @throws LanBracketsRequestException
     */
    public function addParticipant(int $competitionId, array $data): array
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
     * @param  array<int, array{participant_type: string, participant_id: int, seed?: int|null}>  $participants
     * @return array<string, mixed>
     *
     * @throws LanBracketsDisabledException
     * @throws LanBracketsRequestException
     */
    public function bulkAddParticipants(int $competitionId, array $participants): array
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
    public function withdrawParticipant(int $competitionId, int $participantId): void
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
     * @param  array<int, array{participant_id: int, score: int}>  $scores
     * @return array<string, mixed>
     *
     * @throws LanBracketsDisabledException
     * @throws LanBracketsRequestException
     */
    public function reportMatchResult(int $competitionId, int $matchId, array $scores): array
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
    public function getStages(int $competitionId): array
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
     * @return array<int, array<string, mixed>>
     *
     * @throws LanBracketsDisabledException
     * @throws LanBracketsRequestException
     */
    public function getMatches(int $competitionId, int $stageId): array
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
    public function getStandings(int $competitionId): array
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
    public function regenerateShareToken(int $competitionId): string
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
