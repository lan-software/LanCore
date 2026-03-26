<?php

namespace App\Domain\Achievements\Models;

use App\Models\User;
use Database\Factories\AchievementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

#[Fillable([
    'name', 'description', 'notification_text', 'color', 'icon', 'is_active',
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
}
