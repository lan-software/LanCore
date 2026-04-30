<?php

namespace App\Domain\DataLifecycle\Models;

use App\Domain\DataLifecycle\Enums\RetentionDataClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Append-only paper trail of every anonymization or purge run.
 *
 * @see docs/mil-std-498/SSS.md CAP-DL-004, CAP-DL-005
 * @see docs/mil-std-498/SRS.md DL-F-010, DL-F-013
 */
#[Fillable([
    'user_id', 'data_class', 'anonymizer_class',
    'records_scrubbed_count', 'records_kept_under_retention_count',
    'retention_until', 'completed_at', 'summary',
])]
class AnonymizationLogEntry extends Model
{
    public $timestamps = false;

    protected $dates = ['retention_until', 'completed_at', 'created_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data_class' => RetentionDataClass::class,
            'records_scrubbed_count' => 'integer',
            'records_kept_under_retention_count' => 'integer',
            'retention_until' => 'date',
            'completed_at' => 'datetime',
            'created_at' => 'datetime',
            'summary' => 'array',
        ];
    }

    /**
     * The log is append-only. Updates are forbidden at the model layer.
     */
    public function save(array $options = []): bool
    {
        if ($this->exists) {
            throw new LogicException('AnonymizationLogEntry rows are immutable.');
        }

        return parent::save($options);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
