<?php

namespace App\Domain\Achievements\Models;

use App\Models\User;
use Database\Factories\AchievementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @see docs/mil-std-498/SRS.md ACH-F-008
 */
#[Fillable([
    'name', 'description', 'notification_text', 'color', 'icon', 'is_active', 'earned_user_count',
])]
class Achievement extends Model implements AuditableContract
{
    use Auditable;

    /** @use HasFactory<AchievementFactory> */
    use HasFactory;

    protected static function newFactory(): AchievementFactory
    {
        return AchievementFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'earned_user_count' => 'integer',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('earned_at')->withTimestamps();
    }

    public function achievementEvents(): HasMany
    {
        return $this->hasMany(AchievementEvent::class);
    }

    /**
     * Global rarity percentage — `earned_user_count / total_users * 100`,
     * rounded to one decimal. Denominator cached for 60 s so repeated
     * profile renders within the same minute share a single COUNT.
     *
     * @see docs/mil-std-498/SRS.md ACH-F-008
     */
    public function earnedPercentage(): float
    {
        $total = (int) Cache::remember('users.count', 60, fn (): int => User::query()->count());

        return round(((int) $this->earned_user_count / max(1, $total)) * 100, 1);
    }
}
