<?php

namespace App\Domain\Policy\Models;

use Database\Factories\PolicyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

#[Fillable([
    'policy_type_id',
    'key',
    'name',
    'description',
    'is_required_for_registration',
    'sort_order',
    'required_acceptance_version_id',
    'archived_at',
])]
class Policy extends Model implements AuditableContract
{
    use Auditable;

    /** @use HasFactory<PolicyFactory> */
    use HasFactory;

    protected $table = 'policies';

    protected static function newFactory(): PolicyFactory
    {
        return PolicyFactory::new();
    }

    protected function casts(): array
    {
        return [
            'is_required_for_registration' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(PolicyType::class, 'policy_type_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(PolicyVersion::class);
    }

    public function requiredAcceptanceVersion(): BelongsTo
    {
        return $this->belongsTo(PolicyVersion::class, 'required_acceptance_version_id');
    }

    public function currentVersion(): HasOne
    {
        return $this->hasOne(PolicyVersion::class)->ofMany(
            ['version_number' => 'max'],
            fn (Builder $query) => $query->where('published_at', '<=', now()),
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function scopeRequiredForRegistration(Builder $query): Builder
    {
        return $query->where('is_required_for_registration', true);
    }
}
