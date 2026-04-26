<?php

namespace App\Http\Resources;

use App\Domain\Profile\Enums\ProfileVisibility;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Whitelist of fields safe to expose on the public profile page.
 *
 * Real name, email, phone, address, country, and locale are intentionally
 * excluded — see SEC-021. Future contributors: do not add private fields
 * here without first updating the SRS and SSS.
 *
 * @see docs/mil-std-498/SRS.md USR-F-023
 * @see docs/mil-std-498/SSS.md SEC-021
 *
 * @mixin User
 */
class PublicProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $visibility = $this->profile_visibility instanceof ProfileVisibility
            ? $this->profile_visibility
            : ProfileVisibility::LoggedIn;

        return [
            'id' => $this->id,
            'username' => $this->username,
            'profile_emoji' => $this->profile_emoji,
            'short_bio' => $this->short_bio,
            'profile_description' => $this->profile_description,
            'avatar_url' => $this->avatarUrl(),
            'banner_url' => $this->bannerUrl(),
            'profile_visibility' => $visibility->value,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
