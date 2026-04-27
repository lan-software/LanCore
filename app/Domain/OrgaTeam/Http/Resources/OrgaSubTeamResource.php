<?php

namespace App\Domain\OrgaTeam\Http\Resources;

use App\Domain\OrgaTeam\Enums\SubTeamRole;
use App\Domain\OrgaTeam\Models\OrgaSubTeam;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @see docs/mil-std-498/SRS.md OT-F-003, OT-F-004, OT-F-007
 *
 * @mixin OrgaSubTeam
 */
class OrgaSubTeamResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $deputies = $this->whenLoaded('memberships', fn () => $this->memberships
            ->where('role', SubTeamRole::Deputy)
            ->sortBy('sort_order')
            ->values()
            ->map(fn ($m) => $m->user ? OrgaTeamPersonResource::make($m->user)->toArray($request) : null)
            ->filter()
            ->values()
            ->all(), []);

        $members = $this->whenLoaded('memberships', fn () => $this->memberships
            ->where('role', SubTeamRole::Member)
            ->sortBy('sort_order')
            ->values()
            ->map(fn ($m) => $m->user ? OrgaTeamPersonResource::make($m->user)->toArray($request) : null)
            ->filter()
            ->values()
            ->all(), []);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'emoji' => $this->emoji,
            'color' => $this->color,
            'sort_order' => $this->sort_order,
            'leader' => $this->whenLoaded('leader', fn () => $this->leader
                ? OrgaTeamPersonResource::make($this->leader)->toArray($request)
                : null),
            'deputies' => $deputies,
            'members' => $members,
        ];
    }
}
