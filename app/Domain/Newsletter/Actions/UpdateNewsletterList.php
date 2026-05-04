<?php

namespace App\Domain\Newsletter\Actions;

use App\Domain\Newsletter\Clients\ListmonkClient;
use App\Domain\Newsletter\Models\NewsletterList;
use Illuminate\Support\Facades\DB;

/**
 * Updates an existing list. Local fields (`is_user_selectable`,
 * `is_default_public`, `description`) are always persisted; remote
 * Listmonk metadata (`name`, `type`, `optin`, `tags`) is pushed back to
 * Listmonk only when the relevant attribute is included in the payload.
 *
 * Setting `is_default_public = true` is exclusive: any other list
 * carrying the flag is cleared atomically inside the same transaction.
 *
 * @see docs/mil-std-498/SRS.md NLT-F-001
 */
class UpdateNewsletterList
{
    public function __construct(private readonly ListmonkClient $listmonk) {}

    /**
     * @param  array{name?: string, description?: string|null, type?: string, optin?: string, tags?: array<int, string>, is_user_selectable?: bool, is_default_public?: bool}  $attributes
     */
    public function execute(NewsletterList $list, array $attributes): NewsletterList
    {
        return DB::transaction(function () use ($list, $attributes): NewsletterList {
            $remoteFields = array_intersect_key($attributes, array_flip(['name', 'type', 'optin', 'tags', 'description']));

            if ($remoteFields !== []) {
                $this->listmonk->updateList($list->listmonk_id, $remoteFields);
            }

            if (($attributes['is_default_public'] ?? false) === true) {
                NewsletterList::query()
                    ->whereKeyNot($list->id)
                    ->where('is_default_public', true)
                    ->update(['is_default_public' => false]);
            }

            $list->fill([
                'name' => $attributes['name'] ?? $list->name,
                'description' => array_key_exists('description', $attributes) ? $attributes['description'] : $list->description,
                'type' => $attributes['type'] ?? $list->type,
                'optin' => $attributes['optin'] ?? $list->optin,
                'tags' => $attributes['tags'] ?? $list->tags,
                'is_user_selectable' => $attributes['is_user_selectable'] ?? $list->is_user_selectable,
                'is_default_public' => $attributes['is_default_public'] ?? $list->is_default_public,
                'last_synced_at' => now(),
            ])->save();

            return $list;
        });
    }
}
