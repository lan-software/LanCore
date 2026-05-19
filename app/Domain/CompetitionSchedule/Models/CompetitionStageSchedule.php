<?php

namespace App\Domain\CompetitionSchedule\Models;

use App\Domain\Competition\Models\Competition;
use Carbon\CarbonInterface;
use Database\Factories\CompetitionStageScheduleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @see docs/mil-std-498/SRS.md COMP-SCH-001
 */
#[Fillable([
    'competition_id', 'lanbrackets_stage_id', 'stage_name', 'stage_type', 'sequence',
    'starts_at', 'estimated_duration_minutes', 'reserve_buffer_minutes',
    'duration_overridden', 'computed_inputs_hash', 'notes',
])]
class CompetitionStageSchedule extends Model
{
    /** @use HasFactory<CompetitionStageScheduleFactory> */
    use HasFactory;

    use HasUlids;

    protected static function newFactory(): CompetitionStageScheduleFactory
    {
        return CompetitionStageScheduleFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'starts_at' => 'datetime',
            'estimated_duration_minutes' => 'integer',
            'reserve_buffer_minutes' => 'integer',
            'duration_overridden' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Competition, self>
     */
    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function endsAt(): ?CarbonInterface
    {
        if ($this->starts_at === null) {
            return null;
        }

        return $this->starts_at->addMinutes($this->estimated_duration_minutes + $this->reserve_buffer_minutes);
    }

    public function activeRunEndsAt(): ?CarbonInterface
    {
        if ($this->starts_at === null) {
            return null;
        }

        return $this->starts_at->addMinutes($this->estimated_duration_minutes);
    }
}
