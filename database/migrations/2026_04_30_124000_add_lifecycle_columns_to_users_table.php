<?php

use App\Domain\DataLifecycle\Services\EmailHasher;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add the columns required for soft-delete + in-place anonymization
 * + post-deletion GDPR-export lookup on the users table.
 *
 * @see docs/mil-std-498/SSS.md CAP-DL-004, CAP-DL-007
 * @see docs/mil-std-498/SRS.md DL-F-009, DL-F-016
 * @see docs/mil-std-498/DBDD.md §"users (Data Lifecycle columns)"
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->softDeletes();
            $table->timestamp('anonymized_at')->nullable()->after('deleted_at');
            $table->timestamp('pending_deletion_at')->nullable()->after('anonymized_at');
            $table->char('email_hash', 64)->nullable()->after('email')->unique();
        });

        $hasher = app(EmailHasher::class);

        DB::table('users')
            ->whereNull('email_hash')
            ->whereNotNull('email')
            ->orderBy('id')
            ->lazyById(500)
            ->each(function (object $row) use ($hasher): void {
                DB::table('users')
                    ->where('id', $row->id)
                    ->update(['email_hash' => $hasher->hash($row->email)]);
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['email_hash']);
            $table->dropColumn(['email_hash', 'pending_deletion_at', 'anonymized_at']);
            $table->dropSoftDeletes();
        });
    }
};
