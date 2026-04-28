<?php

namespace App\Domain\Policy\Models;

use App\Domain\Policy\Enums\PolicyAcceptanceSource;
use App\Models\User;
use Database\Factories\PolicyAcceptanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

#[Fillable([
    'user_id',
    'policy_version_id',
    'accepted_at',
    'locale',
    'ip_address',
    'user_agent',
    'source',
    'withdrawn_at',
    'withdrawn_reason',
    'withdrawn_ip',
    'withdrawn_user_agent',
])]
class PolicyAcceptance extends Model implements AuditableContract
{
    use Auditable;

    /** @use HasFactory<PolicyAcceptanceFactory> */
    use HasFactory;

    protected static function newFactory(): PolicyAcceptanceFactory
    {
        return PolicyAcceptanceFactory::new();
    }

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
            'withdrawn_at' => 'datetime',
            'source' => PolicyAcceptanceSource::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(PolicyVersion::class, 'policy_version_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('withdrawn_at');
    }
}
