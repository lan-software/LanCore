<?php

namespace App\Domain\Policy\Models;

use Database\Factories\PolicyTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

#[Fillable(['key', 'label', 'description'])]
class PolicyType extends Model implements AuditableContract
{
    use Auditable;

    /** @use HasFactory<PolicyTypeFactory> */
    use HasFactory;

    protected static function newFactory(): PolicyTypeFactory
    {
        return PolicyTypeFactory::new();
    }

    public function policies(): HasMany
    {
        return $this->hasMany(Policy::class);
    }
}
