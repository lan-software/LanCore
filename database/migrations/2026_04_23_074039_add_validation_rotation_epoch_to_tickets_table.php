<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the rotation epoch counter used to derive deterministic ticket nonces
 * via HMAC(pepper, ticket_id || epoch). Legacy rows (epoch = 0) require a
 * one-time rotation via `php artisan tickets:rotate-all` to bring their
 * stored `validation_nonce_hash` onto the deterministic rail. All existing
 * printed QR codes become invalid after that run.
 *
 * @see docs/mil-std-498/IDD.md §3.11
 * @see docs/mil-std-498/DBDD.md §4.4.2
 * @see docs/mil-std-498/SDD.md §3.3.2
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->unsignedBigInteger('validation_rotation_epoch')
                ->default(0)
                ->after('validation_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('validation_rotation_epoch');
        });
    }
};
