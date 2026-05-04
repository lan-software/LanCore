<?php

namespace App\Domain\Newsletter\Actions;

use App\Domain\Newsletter\Clients\ListmonkClient;
use App\Domain\Newsletter\Models\NewsletterList;
use Illuminate\Support\Facades\DB;

/**
 * Pulls the full list catalog from Listmonk and upserts every entry
 * into the local mirror, preserving `is_user_selectable` and
 * `is_default_public` (admin-curated flags). Lists that exist locally
 * but no longer in Listmonk are dropped from the mirror.
 *
 * @see docs/mil-std-498/SRS.md NLT-F-002
 */
class FetchListsFromListmonk
{
    public function __construct(private readonly ListmonkClient $listmonk) {}

    /**
     * @return array{created: int, updated: int, removed: int}
     */
    public function execute(): array
    {
        $remote = $this->fetchAllPages();

        return DB::transaction(function () use ($remote): array {
            $stats = ['created' => 0, 'updated' => 0, 'removed' => 0];
            $seen = [];

            foreach ($remote as $row) {
                $listmonkId = (int) ($row['id'] ?? 0);
                if ($listmonkId === 0) {
                    continue;
                }

                $existing = NewsletterList::query()->where('listmonk_id', $listmonkId)->first();

                if ($existing === null) {
                    NewsletterList::create([
                        'listmonk_id' => $listmonkId,
                        'name' => (string) ($row['name'] ?? "list-{$listmonkId}"),
                        'description' => $row['description'] ?? null,
                        'type' => (string) ($row['type'] ?? 'private'),
                        'optin' => (string) ($row['optin'] ?? 'single'),
                        'tags' => $row['tags'] ?? [],
                        'is_user_selectable' => false,
                        'is_default_public' => false,
                        'last_synced_at' => now(),
                    ]);
                    $stats['created']++;
                } else {
                    $existing->fill([
                        'name' => (string) ($row['name'] ?? $existing->name),
                        'description' => $row['description'] ?? $existing->description,
                        'type' => (string) ($row['type'] ?? $existing->type),
                        'optin' => (string) ($row['optin'] ?? $existing->optin),
                        'tags' => $row['tags'] ?? $existing->tags,
                        'last_synced_at' => now(),
                    ])->save();
                    $stats['updated']++;
                }

                $seen[] = $listmonkId;
            }

            $stats['removed'] = NewsletterList::query()
                ->whereNotIn('listmonk_id', $seen)
                ->delete();

            return $stats;
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchAllPages(): array
    {
        $page = 1;
        $perPage = 100;
        $rows = [];

        while (true) {
            $response = $this->listmonk->listLists($page, $perPage);
            $results = $response['results'] ?? [];

            if ($results === []) {
                break;
            }

            $rows = array_merge($rows, $results);

            $total = (int) ($response['total'] ?? count($rows));
            if (count($rows) >= $total) {
                break;
            }

            $page++;
        }

        return $rows;
    }
}
