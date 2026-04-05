<?php

namespace App\Domain\Orchestration\Handlers;

use App\Domain\Api\Clients\Tmt2Client;
use App\Domain\Games\Models\Game;
use App\Domain\Orchestration\Contracts\Features\SupportsChatFeature;
use App\Domain\Orchestration\Contracts\MatchHandlerContract;
use App\Domain\Orchestration\Models\GameServer;
use App\Domain\Orchestration\Models\MatchChatMessage;
use App\Domain\Orchestration\Models\OrchestrationJob;
use Carbon\Carbon;

/**
 * Match handler for CS2 via TMT2 (Tournament Match Tracker 2).
 *
 * Translates LanCore match configs into TMT2's IMatchCreateDto format
 * and manages match lifecycle via the TMT2 REST API.
 */
class Tmt2MatchHandler implements MatchHandlerContract, SupportsChatFeature
{
    public function __construct(
        private readonly Tmt2Client $client,
    ) {}

    public function supports(Game $game): bool
    {
        $engine = $game->metadata['engine'] ?? null;

        return $engine === 'source2';
    }

    /**
     * @param  array<string, mixed>  $matchConfig
     */
    public function deploy(GameServer $server, array $matchConfig): void
    {
        $credentials = $server->credentials ?? [];

        $tmt2Payload = [
            'gameServer' => [
                'ip' => $server->host,
                'port' => $server->port,
                'rconPassword' => $credentials['rcon_password'] ?? '',
            ],
            'mapPool' => $matchConfig['map_pool'] ?? [],
            'teamA' => $this->buildTeam($matchConfig['team_a'] ?? []),
            'teamB' => $this->buildTeam($matchConfig['team_b'] ?? []),
            'electionSteps' => $matchConfig['election_steps'] ?? [],
            'webhookUrl' => $matchConfig['webhook_url'] ?? null,
            'webhookHeaders' => $matchConfig['webhook_headers'] ?? null,
            'rconCommands' => $matchConfig['rcon_commands'] ?? [
                'init' => [],
                'knife' => [],
                'match' => [],
                'end' => [],
            ],
            'canClinch' => $matchConfig['can_clinch'] ?? true,
            'matchEndAction' => $matchConfig['match_end_action'] ?? 'NONE',
            'mode' => $matchConfig['mode'] ?? 'SINGLE',
            'passthrough' => $matchConfig['passthrough'] ?? null,
        ];

        $this->client->createMatch($tmt2Payload);
    }

    /**
     * @param  array<string, mixed>  $matchConfig
     */
    public function teardown(GameServer $server, array $matchConfig): void
    {
        $tmt2MatchId = $matchConfig['tmt2_match_id'] ?? null;

        if ($tmt2MatchId !== null) {
            $this->client->deleteMatch((string) $tmt2MatchId);
        }
    }

    public function healthCheck(GameServer $server): bool
    {
        return $this->client->login();
    }

    /**
     * @param  array<string, mixed>  $chatData
     */
    public function handleChatMessage(OrchestrationJob $job, array $chatData): void
    {
        MatchChatMessage::create([
            'orchestration_job_id' => $job->id,
            'steam_id' => $chatData['player']['steamId64'] ?? '',
            'player_name' => $chatData['player']['name'] ?? 'Unknown',
            'message' => $chatData['message'] ?? '',
            'is_team_chat' => $chatData['isTeamChat'] ?? false,
            'timestamp' => isset($chatData['timestamp'])
                ? Carbon::parse($chatData['timestamp'])
                : now(),
        ]);
    }

    /**
     * Build TMT2 ITeamCreateDto from LanCore match config team data.
     *
     * @param  array<string, mixed>  $team
     * @return array<string, mixed>
     */
    private function buildTeam(array $team): array
    {
        $dto = [
            'name' => $team['name'] ?? 'TBD',
        ];

        if (isset($team['passthrough'])) {
            $dto['passthrough'] = (string) $team['passthrough'];
        }

        if (isset($team['advantage'])) {
            $dto['advantage'] = (float) $team['advantage'];
        } else {
            $dto['advantage'] = 0;
        }

        if (! empty($team['player_steam_ids'])) {
            $dto['playerSteamIds64'] = $team['player_steam_ids'];
        }

        return $dto;
    }
}
