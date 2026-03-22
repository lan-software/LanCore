<?php

namespace App\Domain\Webhook\Models;

use App\Domain\Webhook\Enums\WebhookEvent;
use Database\Factories\WebhookFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name', 'url', 'event', 'secret', 'description', 'is_active',
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
