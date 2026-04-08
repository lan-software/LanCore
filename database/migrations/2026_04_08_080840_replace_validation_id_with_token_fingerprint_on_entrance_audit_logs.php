<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entrance_audit_logs', function (Blueprint $table): void {
            $table->dropIndex(['validation_id']);
            $table->dropColumn('validation_id');
            $table->string('token_fingerprint', 16)->nullable()->after('ticket_id');
            $table->index('token_fingerprint');
        });
    }

    public function down(): void
    {
        Schema::table('entrance_audit_logs', function (Blueprint $table): void {
            $table->dropIndex(['token_fingerprint']);
            $table->dropColumn('token_fingerprint');
            $table->string('validation_id', 32)->nullable()->after('ticket_id');
            $table->index('validation_id');
        });
    }
};
