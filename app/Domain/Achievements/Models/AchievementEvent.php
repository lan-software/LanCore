<?php

namespace App\Domain\Achievements\Models;

use Database\Factories\AchievementEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'achievement_id', 'event_class',
])]
class AchievementEvent extends Model
{
    /** @use HasFactory<AchievementEventFactory> */
    use HasFactory;

    protected $table = 'achievement_events';

    protected static function newFactory(): AchievementEventFactory
    {
        return AchievementEventFactory::new();
    }

    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class);
    }
}
