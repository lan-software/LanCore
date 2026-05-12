<?php

namespace App\Domain\Chat\Models;

use App\Domain\Chat\Enums\RoomStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @see docs/mil-std-498/SRS.md CHT-F-001
 * @see docs/mil-std-498/DBDD.md §4.20.1
 */
#[Fillable([
    'key', 'title', 'status', 'consumer_domain', 'policy_class',
    'opened_at', 'write_locked_at', 'archived_at', 'created_by',
])]
class ChatRoom extends Model implements AuditableContract
{
    use Auditable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RoomStatus::class,
            'opened_at' => 'datetime',
            'write_locked_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(ChatRoomMembership::class, 'room_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'room_id');
    }

    public function moderationActions(): HasMany
    {
        return $this->hasMany(ChatModerationAction::class, 'room_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
