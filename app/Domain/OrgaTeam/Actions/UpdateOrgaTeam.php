<?php

namespace App\Domain\OrgaTeam\Actions;

use App\Domain\OrgaTeam\Models\OrgaTeam;
use Illuminate\Support\Facades\DB;

/**
 * @see docs/mil-std-498/SRS.md OT-F-001, OT-F-002
 */
class UpdateOrgaTeam
{
    public function __construct(
        private readonly SyncOrgaTeamDeputies $syncOrgaTeamDeputies,
    ) {}

    /**
     * @param  array{name?: string, slug?: string, description?: string|null, organizer_user_id?: int}  $attributes
     * @param  array<int>|null  $deputyUserIds
     */
    public function execute(OrgaTeam $orgaTeam, array $attributes, ?array $deputyUserIds = null): void
    {
        DB::transaction(function () use ($orgaTeam, $attributes, $deputyUserIds): void {
            $orgaTeam->fill($attributes)->save();

            if ($deputyUserIds !== null) {
                $this->syncOrgaTeamDeputies->execute($orgaTeam, $deputyUserIds);
            }
        });
    }
}
