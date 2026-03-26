<?php

namespace App\Domain\Integration\Models;

use Database\Factories\IntegrationTokenFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['integration_app_id', 'name', 'token', 'plain_text_prefix', 'expires_at', 'revoked_at'])]
#[Hidden(['token'])]
class IntegrationToken extends Model
{
    /** @use HasFactory<IntegrationTokenFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    protected static function newFactory(): IntegrationTokenFactory
    {
        return IntegrationTokenFactory::new();
    }

    public function integrationApp(): BelongsTo
    {
        return $this->belongsTo(IntegrationApp::class);
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isUsable(): bool
    {
        return ! $this->isRevoked() && ! $this->isExpired();
    }
}
