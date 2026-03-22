<?php

namespace App\Domain\Sponsoring\Models;

use App\Domain\Event\Models\Event;
use App\Domain\Program\Models\Program;
use App\Domain\Program\Models\TimeSlot;
use App\Models\User;
use Database\Factories\SponsorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

#[Fillable(['name', 'description', 'link', 'logo', 'sponsor_level_id'])]
class Sponsor extends Model implements AuditableContract
{
    use Auditable;

    /** @use HasFactory<SponsorFactory> */
    use HasFactory;

    protected static function newFactory(): SponsorFactory
    {
        return SponsorFactory::new();
    }

    public function sponsorLevel(): BelongsTo
    {
        return $this->belongsTo(SponsorLevel::class);
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class)->withTimestamps();
    }

    public function managers(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class)->withTimestamps();
    }

    public function timeSlots(): BelongsToMany
    {
        return $this->belongsToMany(TimeSlot::class)->withTimestamps();
    }
}
