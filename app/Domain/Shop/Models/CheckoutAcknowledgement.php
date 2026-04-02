<?php

namespace App\Domain\Shop\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @see docs/mil-std-498/SSS.md CAP-SHP-008
 * @see docs/mil-std-498/SRS.md SHP-F-011
 */
#[Fillable([
    'user_id', 'acknowledgeable_type', 'acknowledgeable_id', 'acknowledgement_key', 'acknowledged_at',
])]
class CheckoutAcknowledgement extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'acknowledged_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function acknowledgeable(): MorphTo
    {
        return $this->morphTo();
    }
}
