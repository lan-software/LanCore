<?php

namespace App\Domain\OrgaTeam\Actions;

use App\Domain\OrgaTeam\Models\OrgaTeam;
use Illuminate\Support\Facades\DB;

/**
 * @see docs/mil-std-498/SSS.md CAP-OT-001
 * @see docs/mil-std-498/SRS.md OT-F-001, OT-F-002
 */
class CreateOrgaTeam
{
    public function __construct(
        private readonly SyncOrgaTeamDeputies $syncOrgaTeamDeputies,
    ) {}

    /**
     * @param  array{name: string, slug: string, description?: string|null, organizer_user_id: int}  $attributes
     * @param  array<int>  $deputyUserIds
     */
    public function execute(array $attributes, array $deputyUserIds = []): OrgaTeam
    {
        return DB::transaction(function () use ($attributes, $deputyUserIds): OrgaTeam {
            $team = OrgaTeam::create($attributes);

            if (! empty($deputyUserIds)) {
                $this->syncOrgaTeamDeputies->execute($team, $deputyUserIds);
            }

            return $team;
        });
    }
}
