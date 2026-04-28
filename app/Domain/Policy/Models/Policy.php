<?php

namespace App\Domain\Policy\Models;

use Database\Factories\PolicyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

#[Fillable([
    'policy_type_id',
    'key',
    'name',
    'description',
    'is_required_for_registration',
    'sort_order',
    'required_acceptance_version_number',
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
            'required_acceptance_version_number' => 'integer',
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

    public function drafts(): HasMany
    {
        return $this->hasMany(PolicyLocaleDraft::class);
    }

    public function latestVersionNumber(): ?int
    {
        $value = $this->versions()->max('version_number');

        return $value === null ? null : (int) $value;
    }

    /**
     * Resolve the PolicyVersion to display for the requested locale.
     *
     * Picks the locale row of the latest published version_number, falling back
     * — within that same version_number — to the row with the smallest id when
     * the requested locale has no row.
     */
    public function currentVersionFor(string $locale): ?PolicyVersion
    {
        $versionNumber = $this->latestVersionNumber();

        if ($versionNumber === null) {
            return null;
        }

        return $this->versionForLocale($versionNumber, $locale);
    }

    public function versionForLocale(int $versionNumber, string $locale): ?PolicyVersion
    {
        $rows = PolicyVersion::query()
            ->where('policy_id', $this->id)
            ->where('version_number', $versionNumber)
            ->where('published_at', '<=', now())
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty()) {
            return null;
        }

        return $rows->firstWhere('locale', $locale) ?? $rows->first();
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
