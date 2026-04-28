<?php

namespace App\Domain\OrgaTeam\Gdpr;

use App\Domain\OrgaTeam\Models\OrgaSubTeamMembership;
use App\Domain\Policy\Gdpr\Contracts\GdprDataSource;
use App\Domain\Policy\Gdpr\GdprDataSourceResult;
use App\Domain\Policy\Gdpr\GdprExportContext;
use App\Models\User;

class OrgaTeamDataSource implements GdprDataSource
{
    public function key(): string
    {
        return 'orga_team';
    }

    public function label(): string
    {
        return 'Organisation team / sub-team memberships';
    }

    public function for(User $user, GdprExportContext $context): GdprDataSourceResult
    {
        $memberships = OrgaSubTeamMembership::query()
            ->where('user_id', $user->id)
            ->with('subTeam:id,name,orga_team_id')
            ->orderBy('id')
            ->get()
            ->map(fn (OrgaSubTeamMembership $m) => array_merge(
                $m->attributesToArray(),
                ['sub_team' => $m->subTeam ? [
                    'id' => $m->subTeam->id,
                    'name' => $m->subTeam->name,
                    'orga_team_id' => $m->subTeam->orga_team_id,
                ] : null],
            ))
            ->all();

        return new GdprDataSourceResult(['memberships' => $memberships]);
    }
}
