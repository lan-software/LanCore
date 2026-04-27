<?php

namespace App\Domain\OrgaTeam\Http\Controllers;

use App\Domain\OrgaTeam\Actions\CreateOrgaSubTeam;
use App\Domain\OrgaTeam\Actions\DeleteOrgaSubTeam;
use App\Domain\OrgaTeam\Actions\SyncOrgaSubTeamMemberships;
use App\Domain\OrgaTeam\Actions\UpdateOrgaSubTeam;
use App\Domain\OrgaTeam\Http\Requests\StoreOrgaSubTeamRequest;
use App\Domain\OrgaTeam\Http\Requests\SyncOrgaSubTeamMembersRequest;
use App\Domain\OrgaTeam\Http\Requests\UpdateOrgaSubTeamRequest;
use App\Domain\OrgaTeam\Models\OrgaSubTeam;
use App\Domain\OrgaTeam\Models\OrgaTeam;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

/**
 * @see docs/mil-std-498/SSS.md CAP-OT-002
 * @see docs/mil-std-498/SRS.md OT-F-003, OT-F-004
 */
class OrgaSubTeamController extends Controller
{
    public function __construct(
        private readonly CreateOrgaSubTeam $createOrgaSubTeam,
        private readonly UpdateOrgaSubTeam $updateOrgaSubTeam,
        private readonly DeleteOrgaSubTeam $deleteOrgaSubTeam,
        private readonly SyncOrgaSubTeamMemberships $syncMemberships,
    ) {}

    public function store(StoreOrgaSubTeamRequest $request, OrgaTeam $orgaTeam): RedirectResponse
    {
        $this->authorize('update', $orgaTeam);

        $this->createOrgaSubTeam->execute($orgaTeam, $request->validated());

        return back();
    }

    public function update(UpdateOrgaSubTeamRequest $request, OrgaSubTeam $subTeam): RedirectResponse
    {
        $this->authorize('update', $subTeam);

        $this->updateOrgaSubTeam->execute($subTeam, $request->validated());

        return back();
    }

    public function destroy(OrgaSubTeam $subTeam): RedirectResponse
    {
        $this->authorize('delete', $subTeam);

        $this->deleteOrgaSubTeam->execute($subTeam);

        return back();
    }

    public function syncMembers(SyncOrgaSubTeamMembersRequest $request, OrgaSubTeam $subTeam): RedirectResponse
    {
        $this->authorize('update', $subTeam);

        /** @var array<int, array{user_id: int, role: string}> $memberships */
        $memberships = $request->validated('memberships', []);
        $this->syncMemberships->execute($subTeam, $memberships);

        return back();
    }
}
