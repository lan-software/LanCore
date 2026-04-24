<?php

namespace App\Domain\Notification\Models;

use App\Models\User;
use Database\Factories\NotificationPreferenceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'mail_on_news',
    'mail_on_events',
    'mail_on_news_comments',
    'mail_on_program_time_slots',
    'mail_on_announcements',
    'mail_on_seating',
    'push_on_news',
    'push_on_events',
    'push_on_news_comments',
    'push_on_program_time_slots',
    'push_on_announcements',
    'push_on_seating',
])]
class NotificationPreference extends Model
{
    /** @use HasFactory<NotificationPreferenceFactory> */
    use HasFactory;

    protected static function newFactory(): NotificationPreferenceFactory
    {
        return NotificationPreferenceFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mail_on_news' => 'boolean',
            'mail_on_events' => 'boolean',
            'mail_on_news_comments' => 'boolean',
            'mail_on_program_time_slots' => 'boolean',
            'mail_on_announcements' => 'boolean',
            'mail_on_seating' => 'boolean',
            'push_on_news' => 'boolean',
            'push_on_events' => 'boolean',
            'push_on_news_comments' => 'boolean',
            'push_on_program_time_slots' => 'boolean',
            'push_on_announcements' => 'boolean',
            'push_on_seating' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
