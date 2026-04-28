<?php

namespace App\Domain\Sponsoring\Gdpr;

use App\Domain\Policy\Gdpr\Contracts\GdprDataSource;
use App\Domain\Policy\Gdpr\GdprDataSourceResult;
use App\Domain\Policy\Gdpr\GdprExportContext;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SponsoringDataSource implements GdprDataSource
{
    public function key(): string
    {
        return 'sponsoring';
    }

    public function label(): string
    {
        return 'Sponsor representative associations';
    }

    public function for(User $user, GdprExportContext $context): GdprDataSourceResult
    {
        if (! Schema::hasTable('sponsor_user')) {
            return new GdprDataSourceResult(['sponsor_associations' => []]);
        }

        $rows = DB::table('sponsor_user')
            ->where('user_id', $user->id)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        return new GdprDataSourceResult(['sponsor_associations' => $rows]);
    }
}
