<?php

namespace App\Domain\Policy\Models;

use App\Models\User;
use Database\Factories\PolicyLocaleDraftFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'policy_id',
    'locale',
    'content',
    'updated_by_user_id',
])]
class PolicyLocaleDraft extends Model
{
    /** @use HasFactory<PolicyLocaleDraftFactory> */
    use HasFactory;

    protected static function newFactory(): PolicyLocaleDraftFactory
    {
        return PolicyLocaleDraftFactory::new();
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
