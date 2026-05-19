<?php

namespace App\Domain\Newsletter\Models;

use App\Domain\Newsletter\Enums\SubscriptionStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * Pivot row in `newsletter_list_user`. Tracks per-user/per-list status
 * mirrored from Listmonk so we can render accurate state in the user
 * E-Mail Settings card without round-tripping on every page load.
 *
 * @property int $id
 * @property string $newsletter_list_id
 * @property string $user_id
 * @property int|null $listmonk_subscriber_id
 * @property SubscriptionStatus $status
 * @property Carbon|null $subscribed_at
 * @property Carbon|null $last_synced_at
 *
 * @see docs/mil-std-498/SRS.md NLT-F-002, NLT-F-004
 */
#[Fillable(['newsletter_list_id', 'user_id', 'listmonk_subscriber_id', 'status', 'subscribed_at', 'last_synced_at'])]
class NewsletterSubscription extends Pivot
{
    protected $table = 'newsletter_list_user';

    public $incrementing = true;

    public $timestamps = true;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'listmonk_subscriber_id' => 'integer',
            'status' => SubscriptionStatus::class,
            'subscribed_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function list(): BelongsTo
    {
        return $this->belongsTo(NewsletterList::class, 'newsletter_list_id');
    }
}
