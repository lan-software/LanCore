<?php

namespace App\Domain\Notification\Models;

use App\Domain\Program\Models\Program;
use App\Models\User;
use Database\Factories\ProgramNotificationSubscriptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'program_id'])]
class ProgramNotificationSubscription extends Model
{
    /** @use HasFactory<ProgramNotificationSubscriptionFactory> */
    use HasFactory;

    protected static function newFactory(): ProgramNotificationSubscriptionFactory
    {
        return ProgramNotificationSubscriptionFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }
}
