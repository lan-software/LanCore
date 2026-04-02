<?php

namespace App\Domain\Shop\Models;

use App\Domain\Event\Models\Event;
use App\Domain\Shop\Enums\VoucherType;
use Database\Factories\VoucherFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @see docs/mil-std-498/SSS.md CAP-SHP-005
 * @see docs/mil-std-498/SRS.md SHP-F-007, SHP-F-008
 */
#[Fillable([
    'code', 'type', 'discount_amount', 'discount_percent',
    'max_uses', 'times_used', 'valid_from', 'valid_until',
    'is_active', 'event_id',
])]
class Voucher extends Model implements AuditableContract
{
    use Auditable;

    /** @use HasFactory<VoucherFactory> */
    use HasFactory;

    protected static function newFactory(): VoucherFactory
    {
        return VoucherFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => VoucherType::class,
            'discount_amount' => 'integer',
            'discount_percent' => 'integer',
            'max_uses' => 'integer',
            'times_used' => 'integer',
            'is_active' => 'boolean',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function isValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->valid_from && $now->isBefore($this->valid_from)) {
            return false;
        }

        if ($this->valid_until && $now->isAfter($this->valid_until)) {
            return false;
        }

        if ($this->max_uses !== null && $this->times_used >= $this->max_uses) {
            return false;
        }

        return true;
    }

    /**
     * Calculate the discount for a given subtotal (in cents).
     */
    public function calculateDiscount(int $subtotal): int
    {
        return match ($this->type) {
            VoucherType::FixedAmount => min($this->discount_amount ?? 0, $subtotal),
            VoucherType::Percentage => (int) floor($subtotal * ($this->discount_percent ?? 0) / 100),
        };
    }
}
