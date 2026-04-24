<?php

namespace App\Domain\Seating\Models;

use App\Domain\Ticketing\Models\TicketCategory;
use Database\Factories\SeatPlanBlockFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * A visual grouping of rows and seats within a seat plan.
 *
 * @see docs/mil-std-498/SRS.md SET-F-002, SET-F-011
 */
#[Fillable(['seat_plan_id', 'title', 'color', 'seat_title_prefix', 'background_image_url', 'sort_order'])]
class SeatPlanBlock extends Model implements AuditableContract
{
    use Auditable;

    /** @use HasFactory<SeatPlanBlockFactory> */
    use HasFactory;

    protected static function newFactory(): SeatPlanBlockFactory
    {
        return SeatPlanBlockFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function seatPlan(): BelongsTo
    {
        return $this->belongsTo(SeatPlan::class);
    }

    public function rows(): HasMany
    {
        return $this->hasMany(SeatPlanRow::class)->orderBy('sort_order');
    }

    public function seats(): HasMany
    {
        return $this->hasMany(SeatPlanSeat::class);
    }

    public function labels(): HasMany
    {
        return $this->hasMany(SeatPlanLabel::class)->orderBy('sort_order');
    }

    /**
     * Per-block ticket-category allowlist (SET-F-011). Empty pivot ⇒ open to
     * all categories (permissive default).
     */
    public function categoryRestrictions(): BelongsToMany
    {
        return $this->belongsToMany(
            TicketCategory::class,
            'seat_plan_block_category_restrictions',
            'seat_plan_block_id',
            'ticket_category_id',
        );
    }
}
