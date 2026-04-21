<?php

namespace App\Domain\Competition\Actions;

use App\Domain\Api\Clients\LanBracketsClient;
use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\CompetitionTeam;
use App\Domain\Competition\Models\MatchResultProof;
use App\Models\User;
use App\Support\StorageRole;
use Illuminate\Http\UploadedFile;

/**
 * @see docs/mil-std-498/SRS.md COMP-F-008
 */
class SubmitMatchResult
{
    public function __construct(private readonly LanBracketsClient $client) {}

    /**
     * @param  array<int, array{participant_id: int, score: int}>  $scores
     */
    public function execute(
        Competition $competition,
        int $lanbracketsMatchId,
        array $scores,
        UploadedFile $screenshot,
        User $user,
        ?CompetitionTeam $team = null,
    ): MatchResultProof {
        $path = $screenshot->store('proofs', StorageRole::publicDiskName());

        $proof = MatchResultProof::create([
            'competition_id' => $competition->id,
            'lanbrackets_match_id' => $lanbracketsMatchId,
            'submitted_by_user_id' => $user->id,
            'submitted_by_team_id' => $team?->id,
            'screenshot_path' => $path,
            'scores' => $scores,
        ]);

        if (config('lanbrackets.enabled') && $competition->isSyncedToLanBrackets()) {
            $this->client->reportMatchResult(
                $competition->lanbrackets_id,
                $lanbracketsMatchId,
                $scores,
            );
        }

        return $proof;
    }
}
