<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('policies', function (Blueprint $table): void {
            $table->unsignedInteger('required_acceptance_version_number')
                ->nullable()
                ->after('required_acceptance_version_id');
        });

        DB::statement(<<<'SQL'
            UPDATE policies
            SET required_acceptance_version_number = (
                SELECT version_number
                FROM policy_versions
                WHERE policy_versions.id = policies.required_acceptance_version_id
            )
            WHERE required_acceptance_version_id IS NOT NULL
        SQL);
    }

    public function down(): void
    {
        Schema::table('policies', function (Blueprint $table): void {
            $table->dropColumn('required_acceptance_version_number');
        });
    }
};
