<?php

namespace App\Gdpr;

use App\Domain\Policy\Gdpr\Contracts\GdprDataSource;
use App\Domain\Policy\Gdpr\GdprDataSourceResult;
use App\Domain\Policy\Gdpr\GdprExportContext;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reads from the `audits` table maintained by owen-it/laravel-auditing
 * v14. Exposes audit rows where the subject user is the actor or
 * the auditable entity. Other actors writing on the user's records
 * are pseudonymised.
 */
class AuditDataSource implements GdprDataSource
{
    public function key(): string
    {
        return 'audits';
    }

    public function label(): string
    {
        return 'System audit trail (actions performed on or by the user)';
    }

    public function for(User $user, GdprExportContext $context): GdprDataSourceResult
    {
        if (! Schema::hasTable('audits')) {
            return new GdprDataSourceResult(['audits' => []]);
        }

        $rows = DB::table('audits')
            ->where(function ($q) use ($user): void {
                $q->where('user_id', $user->id)
                    ->orWhere(function ($inner) use ($user): void {
                        $inner->where('auditable_type', User::class)
                            ->where('auditable_id', $user->id);
                    });
            })
            ->orderBy('created_at')
            ->get();

        $records = $rows->map(function ($row) use ($user, $context): array {
            $actorPseudonym = null;
            if ($row->user_id !== null) {
                $actorPseudonym = $row->user_id === $user->id
                    ? 'subject'
                    : $context->obfuscateUser((int) $row->user_id, 'audit actor');
            }

            return [
                'id' => $row->id,
                'event' => $row->event,
                'auditable_type' => $row->auditable_type,
                'auditable_id' => $row->auditable_type === User::class && (int) $row->auditable_id !== $user->id
                    ? $context->obfuscateUser((int) $row->auditable_id, 'audited user')
                    : $row->auditable_id,
                'actor_pseudonym' => $actorPseudonym,
                'old_values' => $row->old_values,
                'new_values' => $row->new_values,
                'url' => $row->url,
                'ip_address' => $row->ip_address,
                'user_agent' => $row->user_agent,
                'created_at' => $row->created_at,
            ];
        })->all();

        return new GdprDataSourceResult(['audits' => $records]);
    }
}
