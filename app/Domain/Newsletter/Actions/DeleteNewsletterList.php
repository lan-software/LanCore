<?php

namespace App\Domain\Newsletter\Actions;

use App\Domain\Newsletter\Clients\ListmonkClient;
use App\Domain\Newsletter\Models\NewsletterList;
use Illuminate\Support\Facades\DB;

/**
 * Deletes the local mirror. Listmonk-side deletion is opt-in: the
 * caller must pass `$alsoDeleteRemote = true` to actually drop the
 * list inside Listmonk (admins can also "demote" a list from the
 * curated set by toggling `is_user_selectable` instead of deleting).
 *
 * @see docs/mil-std-498/SRS.md NLT-F-001
 */
class DeleteNewsletterList
{
    public function __construct(private readonly ListmonkClient $listmonk) {}

    public function execute(NewsletterList $list, bool $alsoDeleteRemote = false): void
    {
        DB::transaction(function () use ($list, $alsoDeleteRemote): void {
            if ($alsoDeleteRemote) {
                $this->listmonk->deleteList($list->listmonk_id);
            }

            $list->delete();
        });
    }
}
