<?php

namespace App\Domain\Auth\Steam\Data;

use Carbon\CarbonImmutable;
use Laravel\Socialite\Contracts\User as SocialiteUser;

/**
 * Steam profile data captured at the OpenID callback, stored in session
 * until the user submits the completion form. Steam does not return an
 * email address, so users must provide one before a User row is created.
 */
final readonly class PendingSteamRegistration
{
    public const SESSION_KEY = 'auth.steam.pending';

    public const TTL_MINUTES = 30;

    public function __construct(
        public string $steamId64,
        public ?string $personaName,
        public ?string $avatarUrl,
        public ?string $profileUrl,
        public ?string $countryCode,
        public CarbonImmutable $createdAt,
    ) {}

    public static function fromSocialiteUser(SocialiteUser $user): self
    {
        $raw = $user->getRaw();

        return new self(
            steamId64: (string) $user->getId(),
            personaName: $user->getNickname() ?: $user->getName(),
            avatarUrl: $user->getAvatar(),
            profileUrl: is_string($raw['profileurl'] ?? null) ? $raw['profileurl'] : null,
            countryCode: is_string($raw['loccountrycode'] ?? null) ? mb_strtolower($raw['loccountrycode']) : null,
            createdAt: CarbonImmutable::now(),
        );
    }

    public function isExpired(): bool
    {
        return $this->createdAt->addMinutes(self::TTL_MINUTES)->isPast();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'steam_id_64' => $this->steamId64,
            'persona_name' => $this->personaName,
            'avatar_url' => $this->avatarUrl,
            'profile_url' => $this->profileUrl,
            'country_code' => $this->countryCode,
            'created_at' => $this->createdAt->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            steamId64: (string) $data['steam_id_64'],
            personaName: isset($data['persona_name']) ? (string) $data['persona_name'] : null,
            avatarUrl: isset($data['avatar_url']) ? (string) $data['avatar_url'] : null,
            profileUrl: isset($data['profile_url']) ? (string) $data['profile_url'] : null,
            countryCode: isset($data['country_code']) ? (string) $data['country_code'] : null,
            createdAt: isset($data['created_at']) ? CarbonImmutable::parse((string) $data['created_at']) : CarbonImmutable::now(),
        );
    }
}
