<?php

namespace App\Domain\Integration\Models;

use App\Domain\Webhook\Models\Webhook;
use Database\Factories\IntegrationAppFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'description', 'callback_url', 'nav_url', 'nav_icon', 'nav_label', 'allowed_scopes', 'is_active', 'send_announcements', 'announcement_endpoint'])]
class IntegrationApp extends Model
{
    /** @use HasFactory<IntegrationAppFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'allowed_scopes' => 'array',
            'is_active' => 'boolean',
            'send_announcements' => 'boolean',
        ];
    }

    protected static function newFactory(): IntegrationAppFactory
    {
        return IntegrationAppFactory::new();
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(IntegrationToken::class);
    }

    public function activeTokens(): HasMany
    {
        return $this->tokens()->whereNull('revoked_at')->where(function ($query): void {
            $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }

    public function webhooks(): HasMany
    {
        return $this->hasMany(Webhook::class);
    }

    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->allowed_scopes ?? [], true);
    }
}
