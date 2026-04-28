<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $latestPerLocale = DB::table('policy_versions')
            ->select('policy_id', 'locale', DB::raw('MAX(version_number) as max_version'))
            ->groupBy('policy_id', 'locale');

        $rows = DB::table('policy_versions as pv')
            ->joinSub($latestPerLocale, 'latest', function ($join): void {
                $join->on('pv.policy_id', '=', 'latest.policy_id')
                    ->on('pv.locale', '=', 'latest.locale')
                    ->on('pv.version_number', '=', 'latest.max_version');
            })
            ->select('pv.policy_id', 'pv.locale', 'pv.content', 'pv.published_by_user_id')
            ->orderBy('pv.policy_id')
            ->orderBy('pv.locale')
            ->get();

        $now = now();
        $payload = $rows->map(fn ($row) => [
            'policy_id' => $row->policy_id,
            'locale' => $row->locale,
            'content' => $row->content,
            'updated_by_user_id' => $row->published_by_user_id,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if ($payload !== []) {
            DB::table('policy_locale_drafts')->insert($payload);
        }
    }

    public function down(): void
    {
        DB::table('policy_locale_drafts')->truncate();
    }
};
