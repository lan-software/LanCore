<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Domain\Announcement\Models\Announcement;
use App\Domain\Notification\Models\NotificationPreference;
use App\Domain\Notification\Models\ProgramNotificationSubscription;
use App\Domain\Sponsoring\Models\Sponsor;
use App\Domain\Ticketing\Models\Ticket;
use App\Enums\RoleName;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use Billable, HasFactory, Notifiable, TwoFactorAuthenticatable;

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
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function hasRole(RoleName $role): bool
    {
        return $this->roles->contains('name', $role);
    }

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

    public function usableTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'user_id');
    }

    public function notificationPreference(): HasOne
    {
        return $this->hasOne(NotificationPreference::class);
    }

    public function programNotificationSubscriptions(): HasMany
    {
        return $this->hasMany(ProgramNotificationSubscription::class);
    }

    public function dismissedAnnouncements(): BelongsToMany
    {
        return $this->belongsToMany(Announcement::class, 'announcement_dismissals')->withTimestamps();
    }
}
