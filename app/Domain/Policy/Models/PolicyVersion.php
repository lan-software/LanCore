<?php

namespace App\Domain\Policy\Models;

use App\Models\User;
use Database\Factories\PolicyVersionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

#[Fillable([
    'policy_id',
    'version_number',
    'locale',
    'content',
    'public_statement',
    'is_non_editorial_change',
    'pdf_path',
    'effective_at',
    'published_at',
    'published_by_user_id',
])]
class PolicyVersion extends Model implements AuditableContract
{
    use Auditable;

    /** @use HasFactory<PolicyVersionFactory> */
    use HasFactory;

    protected static function newFactory(): PolicyVersionFactory
    {
        return PolicyVersionFactory::new();
    }

    protected function casts(): array
    {
        return [
            'is_non_editorial_change' => 'boolean',
            'effective_at' => 'datetime',
            'published_at' => 'datetime',
            'version_number' => 'integer',
        ];
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by_user_id');
    }

    public function acceptances(): HasMany
    {
        return $this->hasMany(PolicyAcceptance::class);
    }

    /**
     * Restrict to all locale rows that share this row's (policy_id, version_number) — i.e.,
     * everything published together in one atomic multi-locale publish.
     */
    public function scopePublishGroup(Builder $query): Builder
    {
        return $query
            ->where('policy_id', $this->policy_id)
            ->where('version_number', $this->version_number);
    }
}
