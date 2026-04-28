<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Concerns\HasPermissions;
use App\Domain\Achievements\Models\Achievement;
use App\Domain\Announcement\Models\Announcement;
use App\Domain\Event\Models\Event;
use App\Domain\Notification\Models\NotificationPreference;
use App\Domain\Notification\Models\ProgramNotificationSubscription;
use App\Domain\Notification\Models\PushSubscription;
use App\Domain\Policy\Models\PolicyAcceptance;
use App\Domain\Policy\Models\PolicyVersion;
use App\Domain\Profile\Actions\ResolveAvatarUrl;
use App\Domain\Profile\Enums\AvatarSource;
use App\Domain\Profile\Enums\ProfileVisibility;
use App\Domain\Shop\Models\Order;
use App\Domain\Sponsoring\Models\Sponsor;
use App\Domain\Ticketing\Models\Ticket;
use App\Enums\RoleName;
use App\Support\StorageRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\URL;
use Laravel\Cashier\Billable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @see docs/mil-std-498/SSS.md CAP-USR-001, CAP-USR-002, CAP-USR-003, CAP-USR-011..014
 * @see docs/mil-std-498/SRS.md USR-F-001, USR-F-002, USR-F-003, USR-F-006, USR-F-022..026
 */
#[Fillable([
    'name', 'email', 'password', 'phone', 'street', 'city', 'zip_code', 'country', 'locale',
    'is_ticket_discoverable', 'ticket_discovery_allowlist', 'is_seat_visible_publicly',
    'sidebar_favorites', 'cookie_preferences',
    'username', 'short_bio', 'profile_description', 'profile_emoji',
    'avatar_source', 'avatar_path', 'banner_path', 'profile_visibility', 'profile_updated_at',
    'steam_id_64', 'steam_linked_at',
])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use Billable, HasFactory, HasPermissions, Notifiable, TwoFactorAuthenticatable;

    /**
     * Check if the user has a complete profile (address + contact) required for purchases.
     */
    public function hasCompleteProfile(): bool
    {
        return $this->street
            && $this->city
            && $this->zip_code
            && $this->country
            && ($this->phone || $this->email);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'is_ticket_discoverable' => 'boolean',
            'ticket_discovery_allowlist' => 'array',
            'is_seat_visible_publicly' => 'boolean',
            'sidebar_favorites' => 'array',
            'cookie_preferences' => 'array',
            'avatar_source' => AvatarSource::class,
            'profile_visibility' => ProfileVisibility::class,
            'profile_updated_at' => 'datetime',
            'steam_linked_at' => 'datetime',
        ];
    }

    /**
     * Whether this user has linked a Steam account.
     */
    public function hasSteam(): bool
    {
        return $this->steam_id_64 !== null;
    }

    /**
     * Whether this user has a usable password set. Steam-only signups have
     * `password = null` until they go through forgot-password to set one.
     */
    public function hasUsablePassword(): bool
    {
        return $this->password !== null && $this->password !== '';
    }

    /**
     * Resolved public-facing avatar URL — never null.
     *
     * Resolution order: custom uploaded → Gravatar → built-in default.
     * The Steam source falls back to default until the Steam-linking
     * iteration ships (see SRS USR-F-024 / SSS CAP-USR-013).
     *
     * @see docs/mil-std-498/SRS.md USR-F-024, ICLIB-F-002 (amended)
     */
    public function avatarUrl(): string
    {
        return app(ResolveAvatarUrl::class)->execute($this);
    }

    /**
     * Resolved public banner URL or null when no banner is set.
     *
     * @see docs/mil-std-498/SRS.md USR-F-024
     */
    public function bannerUrl(): ?string
    {
        return $this->banner_path !== null
            ? StorageRole::publicUrl($this->banner_path)
            : null;
    }

    /**
     * Absolute URL to the user's public profile, or null when no
     * username has been chosen yet.
     *
     * @see docs/mil-std-498/SRS.md USR-F-023, ICLIB-F-002 (amended)
     */
    public function profileUrl(): ?string
    {
        return $this->username !== null
            ? URL::route('public-profile.show', ['username' => $this->username])
            : null;
    }

    protected function profileVisibilityValue(): Attribute
    {
        return Attribute::get(fn (): ProfileVisibility => $this->profile_visibility ?? ProfileVisibility::LoggedIn);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function hasRole(RoleName $role): bool
    {
        return $this->roles->contains('name', $role);
    }

    /**
     * @deprecated Use hasPermission() instead. Will be removed in a future release.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(RoleName::Admin) || $this->hasRole(RoleName::Superadmin);
    }

    public function isSuperadmin(): bool
    {
        return $this->hasRole(RoleName::Superadmin);
    }

    public function managedSponsors(): BelongsToMany
    {
        return $this->belongsToMany(Sponsor::class)->withTimestamps();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @deprecated Use hasPermission() instead. Will be removed in a future release.
     */
    public function isSponsorManager(): bool
    {
        return $this->hasRole(RoleName::SponsorManager);
    }

    public function ownedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'owner_id');
    }

    public function managedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'manager_id');
    }

    public function assignedTickets(): BelongsToMany
    {
        return $this->belongsToMany(Ticket::class, 'ticket_user')
            ->withPivot('checked_in_at')
            ->withTimestamps();
    }

    public function notificationPreference(): HasOne
    {
        return $this->hasOne(NotificationPreference::class);
    }

    public function programNotificationSubscriptions(): HasMany
    {
        return $this->hasMany(ProgramNotificationSubscription::class);
    }

    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    public function achievements(): BelongsToMany
    {
        return $this->belongsToMany(Achievement::class)->withPivot('earned_at')->withTimestamps();
    }

    public function dismissedAnnouncements(): BelongsToMany
    {
        return $this->belongsToMany(Announcement::class, 'announcement_dismissals')->withTimestamps();
    }

    public function policyAcceptances(): HasMany
    {
        return $this->hasMany(PolicyAcceptance::class);
    }

    public function acceptedPolicyVersions(): BelongsToMany
    {
        return $this->belongsToMany(PolicyVersion::class, 'policy_acceptances')
            ->withPivot('accepted_at', 'locale', 'source', 'withdrawn_at')
            ->withTimestamps();
    }

    public function isDiscoverableBy(User $searcher): bool
    {
        if ($this->is_ticket_discoverable) {
            return true;
        }

        $allowlist = $this->ticket_discovery_allowlist ?? [];

        return in_array($searcher->name, $allowlist, true);
    }

    /**
     * Whether this user's name may be shown next to their seat on the public seat plan
     * for the given event when viewed by the given (possibly anonymous) viewer.
     *
     * Public visibility honours the user's `is_seat_visible_publicly` toggle, BUT a viewer
     * who themselves owns/manages/uses any ticket for the same event always sees the name —
     * so attendees can find each other regardless of the privacy setting.
     *
     * @see docs/mil-std-498/SRS.md SET-F-009, SET-F-010
     */
    public function isSeatNameVisibleTo(?User $viewer, Event $event): bool
    {
        if ($this->is_seat_visible_publicly) {
            return true;
        }

        if ($viewer === null) {
            return false;
        }

        if ($viewer->id === $this->id) {
            return true;
        }

        return $event->tickets()
            ->where(function ($query) use ($viewer): void {
                $query->where('owner_id', $viewer->id)
                    ->orWhere('manager_id', $viewer->id)
                    ->orWhereHas('users', fn ($users) => $users->whereKey($viewer->id));
            })
            ->exists();
    }
}
