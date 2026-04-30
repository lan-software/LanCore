<?php

namespace App\Domain\DataLifecycle\Models;

use App\Domain\DataLifecycle\Enums\DeletionInitiator;
use App\Domain\DataLifecycle\Enums\DeletionRequestStatus;
use App\Models\User;
use Database\Factories\DeletionRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @see docs/mil-std-498/SSS.md CAP-DL-001, CAP-DL-002, CAP-DL-003
 * @see docs/mil-std-498/SRS.md DL-F-001..DL-F-008
 */
#[Fillable([
    'user_id', 'initiator', 'requested_by_user_id', 'requested_by_admin_id',
    'status', 'reason', 'email_confirmation_token', 'email_confirmed_at',
    'scheduled_for', 'anonymized_at', 'force_deleted_at', 'cancelled_at',
    'metadata',
])]
class DeletionRequest extends Model implements AuditableContract
{
    use Auditable;

    /** @use HasFactory<DeletionRequestFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'initiator' => DeletionInitiator::class,
            'status' => DeletionRequestStatus::class,
            'email_confirmed_at' => 'datetime',
            'scheduled_for' => 'datetime',
            'anonymized_at' => 'datetime',
            'force_deleted_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function requestedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id')->withTrashed();
    }

    public function requestedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_admin_id')->withTrashed();
    }
}
