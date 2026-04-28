<?php

namespace App\Domain\Competition\Gdpr;

use App\Domain\Competition\Models\CompetitionTeamMember;
use App\Domain\Competition\Models\MatchResultProof;
use App\Domain\Competition\Models\TeamInvite;
use App\Domain\Competition\Models\TeamJoinRequest;
use App\Domain\Policy\Gdpr\Contracts\GdprDataSource;
use App\Domain\Policy\Gdpr\GdprDataSourceResult;
use App\Domain\Policy\Gdpr\GdprExportContext;
use App\Models\User;

class CompetitionDataSource implements GdprDataSource
{
    public function key(): string
    {
        return 'competitions';
    }

    public function label(): string
    {
        return 'Team memberships, invites, join requests, match-result submissions';
    }

    public function for(User $user, GdprExportContext $context): GdprDataSourceResult
    {
        $memberships = CompetitionTeamMember::query()
            ->where('user_id', $user->id)
            ->orderBy('id')
            ->get()
            ->map->attributesToArray()
            ->all();

        $invites = TeamInvite::query()
            ->where(function ($q) use ($user): void {
                $q->where('user_id', $user->id)
                    ->orWhere('invited_by', $user->id);
            })
            ->orderBy('id')
            ->get()
            ->map(function (TeamInvite $invite) use ($user, $context): array {
                $row = $invite->attributesToArray();
                $row['invited_by'] = $invite->invited_by === $user->id
                    ? 'subject'
                    : ($invite->invited_by ? $context->obfuscateUser($invite->invited_by, 'inviter') : null);
                $row['user_id'] = $invite->user_id === $user->id
                    ? 'subject'
                    : ($invite->user_id ? $context->obfuscateUser($invite->user_id, 'invitee') : null);

                return $row;
            })
            ->all();

        $joinRequests = TeamJoinRequest::query()
            ->where('user_id', $user->id)
            ->orWhere('resolved_by', $user->id)
            ->orderBy('id')
            ->get()
            ->map(function (TeamJoinRequest $tjr) use ($user, $context): array {
                $row = $tjr->attributesToArray();
                $row['resolved_by'] = $tjr->resolved_by === $user->id
                    ? 'subject'
                    : ($tjr->resolved_by ? $context->obfuscateUser($tjr->resolved_by, 'resolver') : null);

                return $row;
            })
            ->all();

        $proofs = MatchResultProof::query()
            ->where('submitted_by_user_id', $user->id)
            ->orderBy('id')
            ->get()
            ->map->attributesToArray()
            ->all();

        return new GdprDataSourceResult([
            'team_memberships' => $memberships,
            'invites' => $invites,
            'join_requests' => $joinRequests,
            'match_result_proofs' => $proofs,
        ]);
    }
}
