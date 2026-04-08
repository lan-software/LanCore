<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table): void {
            $table->string('audience', 32)->default('lancore_only')->after('priority');
        });

        DB::table('announcements')->update(['audience' => 'lancore_only']);

        Schema::table('announcements', function (Blueprint $table): void {
            $table->foreignId('event_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table): void {
            $table->dropColumn('audience');
        });
    }
};
