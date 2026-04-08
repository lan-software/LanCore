<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Widen column to fit new token format: "TKT-" + 32 hex chars = 36 chars
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('validation_id', 64)->change();
        });

        // Also widen the audit log column
        Schema::table('entrance_audit_logs', function (Blueprint $table) {
            $table->string('validation_id', 64)->nullable()->change();
        });

        // Regenerate all existing validation IDs with the new format
        DB::table('tickets')->eachById(function ($ticket) {
            DB::table('tickets')
                ->where('id', $ticket->id)
                ->update(['validation_id' => 'TKT-'.bin2hex(random_bytes(16))]);
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('validation_id', 16)->change();
        });

        Schema::table('entrance_audit_logs', function (Blueprint $table) {
            $table->string('validation_id', 32)->nullable()->change();
        });
    }
};
