<?php

namespace App\Domain\Newsletter\Models;

use App\Models\User;
use Database\Factories\NewsletterListFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * Local mirror of a Listmonk list. We never own list content (campaigns,
 * templates, message bodies) — those live in Listmonk; we only track the
 * minimum metadata needed to render an admin curation surface and to
 * decide whether a list is selectable by end users.
 *
 * `is_user_selectable` gates whether the list shows up in the user
 * E-Mail Settings card. `is_default_public` flags the single list the
 * public `/countdown` form subscribes anonymous emails to (one row max
 * — enforced at the action level, not by a DB constraint, so admins can
 * swap the flag atomically).
 *
 * @property int $id
 * @property int $listmonk_id
 * @property string $name
 * @property string|null $description
 * @property string $type
 * @property string $optin
 * @property array<int, string>|null $tags
 * @property bool $is_user_selectable
 * @property bool $is_default_public
 * @property Carbon|null $last_synced_at
 *
 * @see docs/mil-std-498/SSS.md CAP-NLT-001..004
 * @see docs/mil-std-498/SRS.md NLT-F-001, NLT-F-002
 * @see docs/mil-std-498/SDD.md §5.12
 */
#[Fillable([
    'listmonk_id', 'name', 'description', 'type', 'optin', 'tags',
    'is_user_selectable', 'is_default_public', 'last_synced_at',
])]
class NewsletterList extends Model
{
    /** @use HasFactory<NewsletterListFactory> */
    use HasFactory;

    protected static function newFactory(): NewsletterListFactory
    {
        return NewsletterListFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'listmonk_id' => 'integer',
            'tags' => 'array',
            'is_user_selectable' => 'boolean',
            'is_default_public' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function subscribers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'newsletter_list_user')
            ->using(NewsletterSubscription::class)
            ->withPivot([
                'listmonk_subscriber_id',
                'status',
                'subscribed_at',
                'last_synced_at',
            ])
            ->withTimestamps();
    }
}
