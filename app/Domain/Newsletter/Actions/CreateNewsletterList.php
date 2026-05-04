<?php

namespace App\Domain\Newsletter\Actions;

use App\Domain\Newsletter\Clients\ListmonkClient;
use App\Domain\Newsletter\Models\NewsletterList;
use Illuminate\Support\Facades\DB;

/**
 * Creates a Listmonk list (POSTs to Listmonk's `/api/lists`), then
 * mirrors the returned record locally.
 *
 * @see docs/mil-std-498/SRS.md NLT-F-001
 */
class CreateNewsletterList
{
    public function __construct(private readonly ListmonkClient $listmonk) {}

    /**
     * @param  array{name: string, description?: string|null, type?: string, optin?: string, tags?: array<int, string>, is_user_selectable?: bool, is_default_public?: bool}  $attributes
     */
    public function execute(array $attributes): NewsletterList
    {
        return DB::transaction(function () use ($attributes): NewsletterList {
            $remote = $this->listmonk->createList([
                'name' => $attributes['name'],
                'type' => $attributes['type'] ?? 'private',
                'optin' => $attributes['optin'] ?? 'single',
                'tags' => $attributes['tags'] ?? [],
                'description' => $attributes['description'] ?? null,
            ]);

            return NewsletterList::create([
                'listmonk_id' => (int) $remote['id'],
                'name' => $remote['name'] ?? $attributes['name'],
                'description' => $attributes['description'] ?? null,
                'type' => $remote['type'] ?? ($attributes['type'] ?? 'private'),
                'optin' => $remote['optin'] ?? ($attributes['optin'] ?? 'single'),
                'tags' => $remote['tags'] ?? ($attributes['tags'] ?? []),
                'is_user_selectable' => $attributes['is_user_selectable'] ?? false,
                'is_default_public' => $attributes['is_default_public'] ?? false,
                'last_synced_at' => now(),
            ]);
        });
    }
}
