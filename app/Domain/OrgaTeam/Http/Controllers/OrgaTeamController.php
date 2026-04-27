<?php

namespace App\Domain\OrgaTeam\Http\Controllers;

use App\Domain\OrgaTeam\Actions\CreateOrgaTeam;
use App\Domain\OrgaTeam\Actions\DeleteOrgaTeam;
use App\Domain\OrgaTeam\Actions\UpdateOrgaTeam;
use App\Domain\OrgaTeam\Http\Requests\StoreOrgaTeamRequest;
use App\Domain\OrgaTeam\Http\Requests\UpdateOrgaTeamRequest;
use App\Domain\OrgaTeam\Models\OrgaTeam;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @see docs/mil-std-498/SSS.md CAP-OT-001, CAP-OT-002
 * @see docs/mil-std-498/SRS.md OT-F-001, OT-F-002, OT-F-006
 */
class OrgaTeamController extends Controller
{
    public function __construct(
        private readonly CreateOrgaTeam $createOrgaTeam,
        private readonly UpdateOrgaTeam $updateOrgaTeam,
        private readonly DeleteOrgaTeam $deleteOrgaTeam,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', OrgaTeam::class);

        $teams = OrgaTeam::query()
            ->with(['organizer:id,username,name'])
            ->withCount(['subTeams', 'events'])
            ->orderBy('name')
            ->get();

        return Inertia::render('orga-teams/Index', [
            'orgaTeams' => $teams,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', OrgaTeam::class);

        return Inertia::render('orga-teams/Create', [
            'users' => $this->userOptions(),
        ]);
    }

    public function store(StoreOrgaTeamRequest $request): RedirectResponse
    {
        $this->authorize('create', OrgaTeam::class);

        $data = $request->safe()->except(['deputy_user_ids']);
        $team = $this->createOrgaTeam->execute($data, $request->validated('deputy_user_ids', []));

        return redirect()->route('orga-teams.edit', $team);
    }

    public function edit(OrgaTeam $orgaTeam): Response
    {
        $this->authorize('update', $orgaTeam);

        $orgaTeam->load([
            'organizer:id,username,name',
            'deputies:id,username,name',
            'subTeams.leader:id,username,name',
            'subTeams.memberships.user:id,username,name',
            'events:id,name,orga_team_id',
        ]);

        return Inertia::render('orga-teams/Edit', [
            'orgaTeam' => $orgaTeam,
            'users' => $this->userOptions(),
        ]);
    }

    public function update(UpdateOrgaTeamRequest $request, OrgaTeam $orgaTeam): RedirectResponse
    {
        $this->authorize('update', $orgaTeam);

        $data = $request->safe()->except(['deputy_user_ids']);
        $deputyIds = $request->has('deputy_user_ids') ? $request->validated('deputy_user_ids', []) : null;
        $this->updateOrgaTeam->execute($orgaTeam, $data, $deputyIds);

        return back();
    }

    public function destroy(OrgaTeam $orgaTeam): RedirectResponse
    {
        $this->authorize('delete', $orgaTeam);

        $this->deleteOrgaTeam->execute($orgaTeam);

        return redirect()->route('orga-teams.index');
    }

    /**
     * @return Collection<int, User>
     */
    private function userOptions(): Collection
    {
        return User::query()->orderBy('name')->get(['id', 'username', 'name']);
    }
}
