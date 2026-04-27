<?php

namespace App\Domain\OrgaTeam\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public-safe person card payload for the OrgaTeam OrgChart.
 *
 * @see docs/mil-std-498/SRS.md OT-F-007
 *
 * @mixin User
 */
class OrgaTeamPersonResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'profile_emoji' => $this->profile_emoji,
            'avatar_url' => $this->avatarUrl(),
        ];
    }
}
