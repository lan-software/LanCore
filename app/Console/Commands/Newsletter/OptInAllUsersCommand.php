<?php

namespace App\Console\Commands\Newsletter;

use App\Domain\Newsletter\Actions\OptInAllUsersToList;
use App\Domain\Newsletter\Models\NewsletterList;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('newsletter:opt-in-all
    {list : Newsletter list ID (local) to opt all verified users into}
')]
#[Description('Queue per-user subscribe jobs for every verified user against the given list.')]
class OptInAllUsersCommand extends Command
{
    public function handle(OptInAllUsersToList $action): int
    {
        $listId = (int) $this->argument('list');
        $list = NewsletterList::find($listId);

        if ($list === null) {
            $this->error("Newsletter list #{$listId} not found.");

            return self::FAILURE;
        }

        $count = $action->execute($list);
        $this->info("Queued opt-in jobs for {$count} verified users into list '{$list->name}'.");

        return self::SUCCESS;
    }
}
