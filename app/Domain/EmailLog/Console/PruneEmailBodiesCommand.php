<?php

namespace App\Domain\EmailLog\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PruneEmailBodiesCommand extends Command
{
    protected $signature = 'email-log:prune-bodies';

    protected $description = 'Truncate html/text bodies of email_messages older than the configured retention window.';

    public function handle(): int
    {
        $days = (int) config('email-log.body_retention_days', 0);

        if ($days <= 0) {
            $this->info('Body retention disabled (email-log.body_retention_days = 0). Nothing to do.');

            return self::SUCCESS;
        }

        if (! Schema::hasTable('email_messages')) {
            $this->info('email_messages table does not exist. Nothing to do.');

            return self::SUCCESS;
        }

        $cutoff = now()->subDays($days);

        $updated = DB::table('email_messages')
            ->where('created_at', '<', $cutoff)
            ->whereNotNull('html_body')
            ->orWhere(function ($q) use ($cutoff): void {
                $q->where('created_at', '<', $cutoff)
                    ->whereNotNull('text_body');
            })
            ->update([
                'html_body' => null,
                'text_body' => null,
                'updated_at' => now(),
            ]);

        $this->info("Pruned bodies on {$updated} email_messages older than {$days} days.");

        return self::SUCCESS;
    }
}
