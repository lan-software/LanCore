<?php

namespace App\Domain\OrgaTeam\Http\Resources;

use App\Domain\OrgaTeam\Models\OrgaTeam;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @see docs/mil-std-498/SRS.md OT-F-001, OT-F-002, OT-F-007
 *
 * @mixin OrgaTeam
 */
class OrgaTeamResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'organizer' => $this->whenLoaded('organizer', fn () => $this->organizer
                ? OrgaTeamPersonResource::make($this->organizer)->toArray($request)
                : null),
            'deputies' => $this->whenLoaded('deputies', fn () => $this->deputies
                ->map(fn ($u) => OrgaTeamPersonResource::make($u)->toArray($request))
                ->all(), []),
            'sub_teams' => $this->whenLoaded('subTeams', fn () => OrgaSubTeamResource::collection($this->subTeams)->resolve($request), []),
        ];
    }
}
