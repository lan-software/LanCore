<?php

namespace App\Gdpr;

use App\Domain\Policy\Gdpr\Contracts\GdprDataSource;
use App\Domain\Policy\Gdpr\GdprDataSourceResult;
use App\Domain\Policy\Gdpr\GdprExportContext;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SessionsDataSource implements GdprDataSource
{
    public function key(): string
    {
        return 'sessions';
    }

    public function label(): string
    {
        return 'Active and recent web sessions';
    }

    public function for(User $user, GdprExportContext $context): GdprDataSourceResult
    {
        if (! Schema::hasTable('sessions')) {
            return new GdprDataSourceResult(['sessions' => []]);
        }

        $rows = DB::table('sessions')
            ->where('user_id', $user->id)
            ->get(['id', 'ip_address', 'user_agent', 'last_activity'])
            ->map(fn ($row) => [
                'id' => $row->id,
                'ip_address' => $row->ip_address,
                'user_agent' => $row->user_agent,
                'last_activity' => $row->last_activity,
            ])
            ->all();

        return new GdprDataSourceResult(['sessions' => $rows]);
    }
}
