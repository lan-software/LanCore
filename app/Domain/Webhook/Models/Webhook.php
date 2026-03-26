<?php

namespace App\Domain\Webhook\Models;

use App\Domain\Integration\Models\IntegrationApp;
use App\Domain\Webhook\Enums\WebhookEvent;
use Database\Factories\WebhookFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name', 'url', 'event', 'secret', 'description', 'is_active', 'integration_app_id',
])]
class Webhook extends Model
{
    /** @use HasFactory<WebhookFactory> */
    use HasFactory;

    protected static function newFactory(): WebhookFactory
    {
        return WebhookFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event' => WebhookEvent::class,
            'is_active' => 'boolean',
        ];
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class)->orderByDesc('fired_at');
    }

    public function integrationApp(): BelongsTo
    {
        return $this->belongsTo(IntegrationApp::class);
    }

    public function isManaged(): bool
    {
        return $this->integration_app_id !== null;
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeForEvent(Builder $query, WebhookEvent $event): void
    {
        $query->where('event', $event->value);
    }
}
