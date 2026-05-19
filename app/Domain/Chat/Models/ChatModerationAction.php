<?php

namespace App\Domain\Chat\Models;

use App\Domain\Chat\Enums\ModerationAction as ModerationActionEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @see docs/mil-std-498/SRS.md CHT-F-013
 * @see docs/mil-std-498/DBDD.md §4.20.4
 */
#[Fillable([
    'room_id', 'actor_id', 'target_user_id', 'action', 'reason', 'expires_at',
])]
class ChatModerationAction extends Model implements AuditableContract
{
    use Auditable;
    use HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => ModerationActionEnum::class,
            'expires_at' => 'datetime',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(ChatRoom::class, 'room_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }
}
