<?php

namespace App\Domain\Announcement\Models;

use App\Domain\Announcement\Enums\AnnouncementPriority;
use App\Domain\Event\Models\Event;
use App\Models\User;
use Database\Factories\AnnouncementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

#[Fillable([
    'title', 'description', 'priority', 'event_id', 'author_id', 'published_at',
])]
class Announcement extends Model implements AuditableContract
{
    use Auditable;

    /** @use HasFactory<AnnouncementFactory> */
    use HasFactory;

    protected static function newFactory(): AnnouncementFactory
    {
        return AnnouncementFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'priority' => AnnouncementPriority::class,
            'published_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function dismissedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'announcement_dismissals')->withTimestamps();
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeNotDismissedBy(Builder $query, User $user): void
    {
        $query->whereDoesntHave('dismissedByUsers', fn (Builder $q) => $q->where('users.id', $user->id));
    }
}
