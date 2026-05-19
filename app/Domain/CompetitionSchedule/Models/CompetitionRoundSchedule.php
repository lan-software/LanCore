<?php

namespace App\Domain\CompetitionSchedule\Models;

use Carbon\CarbonInterface;
use Database\Factories\CompetitionRoundScheduleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @see docs/mil-std-498/SRS.md COMP-RND-001
 */
#[Fillable([
    'stage_schedule_id', 'lanbrackets_round_number', 'sequence', 'label',
    'starts_at', 'estimated_duration_minutes', 'reserve_buffer_minutes',
    'duration_overridden', 'computed_inputs_hash', 'notes',
])]
class CompetitionRoundSchedule extends Model
{
    /** @use HasFactory<CompetitionRoundScheduleFactory> */
    use HasFactory;

    use HasUlids;

    protected static function newFactory(): CompetitionRoundScheduleFactory
    {
        return CompetitionRoundScheduleFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lanbrackets_round_number' => 'integer',
            'sequence' => 'integer',
            'starts_at' => 'datetime',
            'estimated_duration_minutes' => 'integer',
            'reserve_buffer_minutes' => 'integer',
            'duration_overridden' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<CompetitionStageSchedule, self>
     */
    public function stageSchedule(): BelongsTo
    {
        return $this->belongsTo(CompetitionStageSchedule::class, 'stage_schedule_id');
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
