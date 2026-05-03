<?php

namespace App\Domain\DataLifecycle\Anonymizers;

use App\Domain\DataLifecycle\Anonymizers\Contracts\DomainAnonymizer;
use App\Domain\DataLifecycle\DTOs\AnonymizationResult;
use App\Domain\DataLifecycle\Enums\AnonymizationMode;
use App\Domain\DataLifecycle\Enums\RetentionDataClass;
use App\Domain\DataLifecycle\Support\AnonymizedNameGenerator;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * In-place scrub of all PII columns on the {@see User} row.
 *
 * Bypasses the {@see User::booted()} email mutator by writing
 * directly to the users table, so the original `email_hash` is preserved as
 * the post-deletion GDPR-export lookup key.
 *
 * @see docs/mil-std-498/SSS.md CAP-DL-004, CAP-DL-007
 * @see docs/mil-std-498/SRS.md DL-F-009
 */
final class UserAnonymizer implements DomainAnonymizer
{
    public function __construct(private AnonymizedNameGenerator $nameGenerator) {}

    public function dataClass(): RetentionDataClass
    {
        return RetentionDataClass::UsersProfile;
    }

    public function anonymize(User $user, AnonymizationMode $mode): AnonymizationResult
    {
        if ($user->isAnonymized() && $mode === AnonymizationMode::Anonymize) {
            return AnonymizationResult::nothingToDo();
        }

        $now = now();

        $payload = [
            'name' => $this->nameGenerator->displayName($user),
            'username' => $this->nameGenerator->username($user),
            'email' => $this->nameGenerator->placeholderEmail($user),
            'phone' => null,
            'street' => null,
            'city' => null,
            'zip_code' => null,
            'country' => null,
            'short_bio' => null,
            'profile_description' => null,
            'profile_emoji' => null,
            'avatar_path' => null,
            'banner_path' => null,
            'avatar_source' => 'default',
            'steam_id_64' => null,
            'steam_linked_at' => null,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'remember_token' => null,
            'ticket_discovery_allowlist' => null,
            'sidebar_favorites' => null,
            'cookie_preferences' => null,
            'password' => Hash::make(Str::random(64)),
            'anonymized_at' => $now,
            'deleted_at' => $now,
            'pending_deletion_at' => null,
            'updated_at' => $now,
        ];

        DB::table('users')->where('id', $user->getKey())->update($payload);

        $user->refresh();

        return new AnonymizationResult(
            recordsScrubbed: 1,
            recordsKeptUnderRetention: 0,
            retentionUntil: null,
            summary: ['scrubbed_columns' => array_keys($payload)],
        );
    }
}
