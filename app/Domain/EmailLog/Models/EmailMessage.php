<?php

namespace App\Domain\EmailLog\Models;

use App\Domain\EmailLog\Enums\EmailMessageStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'message_id', 'mailer',
    'from_address', 'from_name',
    'to_addresses', 'cc_addresses', 'bcc_addresses',
    'subject', 'html_body', 'text_body', 'headers', 'tags',
    'status', 'error',
    'source', 'source_label',
    'notifiable_type', 'notifiable_id',
    'parent_id',
    'sent_at', 'failed_at',
])]
class EmailMessage extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'to_addresses' => 'array',
            'cc_addresses' => 'array',
            'bcc_addresses' => 'array',
            'headers' => 'array',
            'tags' => 'array',
            'status' => EmailMessageStatus::class,
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }
}
