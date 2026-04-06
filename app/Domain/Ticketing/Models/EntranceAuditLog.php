<?php

namespace App\Domain\Ticketing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntranceAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'ticket_id',
        'validation_id',
        'action',
        'decision',
        'operator_id',
        'operator_session',
        'client_info',
        'override_reason',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'json',
            'created_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
