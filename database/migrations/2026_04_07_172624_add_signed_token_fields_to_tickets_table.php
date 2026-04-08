<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @see docs/mil-std-498/DBDD.md §4.4.2
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->char('validation_nonce_hash', 64)->nullable()->unique();
            $table->string('validation_kid', 16)->nullable();
            $table->timestamp('validation_issued_at')->nullable();
            $table->timestamp('validation_expires_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropUnique(['validation_nonce_hash']);
            $table->dropColumn([
                'validation_nonce_hash',
                'validation_kid',
                'validation_issued_at',
                'validation_expires_at',
            ]);
        });
    }
};
