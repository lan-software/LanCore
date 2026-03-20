<?php

namespace App\Domain\Program\Models;

use App\Domain\Event\Models\Event;
use App\Domain\Program\Enums\ProgramVisibility;
use App\Domain\Sponsoring\Models\Sponsor;
use Database\Factories\ProgramFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'visibility', 'event_id', 'sort_order'])]
class Program extends Model
{
    /** @use HasFactory<ProgramFactory> */
    use HasFactory;

    protected static function newFactory(): ProgramFactory
    {
        return ProgramFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visibility' => ProgramVisibility::class,
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function timeSlots(): HasMany
    {
        return $this->hasMany(TimeSlot::class)->orderBy('starts_at');
    }

    public function sponsors(): BelongsToMany
    {
        return $this->belongsToMany(Sponsor::class)->withTimestamps();
    }
}
