<?php

namespace App\Domain\Auth\Steam\Actions;

use App\Domain\Auth\Steam\Data\PendingSteamRegistration;
use App\Domain\Policy\Actions\RecordPolicyAcceptance;
use App\Domain\Policy\Enums\PolicyAcceptanceSource;
use App\Domain\Policy\Models\Policy;
use App\Domain\Policy\Models\PolicyVersion;
use App\Domain\Profile\Enums\AvatarSource;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Creates a LanCore user from a {@see PendingSteamRegistration} plus the
 * email/profile details the user provided on the completion form. Steam
 * signups have a null password until the user later sets one via the
 * forgot-password flow.
 */
class CompleteSteamRegistration
{
    public function __construct(private RecordPolicyAcceptance $recordPolicyAcceptance) {}

    /**
     * @param  array<string, mixed>  $input
     * @param  array<int, PolicyVersion>  $requiredPolicyVersions
     */
    public function execute(
        PendingSteamRegistration $pending,
        array $input,
        array $requiredPolicyVersions,
        Request $request,
    ): User {
        return DB::transaction(function () use ($pending, $input, $requiredPolicyVersions, $request): User {
            $user = User::create([
                'name' => $input['name'],
                'username' => $input['username'],
                'email' => $input['email'],
                'password' => null,
                'steam_id_64' => $pending->steamId64,
                'steam_linked_at' => now(),
                'avatar_source' => AvatarSource::Steam,
                'country' => $pending->countryCode !== null ? mb_strtoupper($pending->countryCode) : null,
            ]);

            foreach ($requiredPolicyVersions as $version) {
                $this->recordPolicyAcceptance->execute(
                    $user,
                    $version,
                    PolicyAcceptanceSource::Registration,
                    $request,
                );
            }

            event(new Registered($user));

            return $user;
        });
    }

    /**
     * Resolve the policy versions that must be accepted on Steam signup —
     * mirrors the set used by the email/password registration flow.
     *
     * @return array<int, PolicyVersion>
     */
    public function resolveRequiredPolicyVersions(): array
    {
        $locale = (string) app()->getLocale();

        return Policy::query()
            ->active()
            ->requiredForRegistration()
            ->get()
            ->map(fn (Policy $policy) => $policy->currentVersionFor($locale))
            ->filter()
            ->values()
            ->all();
    }
}
