<?php

namespace App\Domain\DataLifecycle\Models;

use App\Domain\DataLifecycle\Enums\RetentionDataClass;
use App\Models\User;
use Database\Factories\RetentionPolicyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @see docs/mil-std-498/SSS.md CAP-DL-005
 * @see docs/mil-std-498/SRS.md DL-F-011, DL-F-012
 */
#[Fillable([
    'data_class', 'retention_days', 'legal_basis',
    'can_be_force_deleted', 'description', 'updated_by_user_id',
])]
class RetentionPolicy extends Model implements AuditableContract
{
    use Auditable;

    /** @use HasFactory<RetentionPolicyFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data_class' => RetentionDataClass::class,
            'retention_days' => 'integer',
            'can_be_force_deleted' => 'boolean',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public static function forClass(RetentionDataClass $class): self
    {
        return self::query()->where('data_class', $class->value)->firstOrFail();
    }
}
