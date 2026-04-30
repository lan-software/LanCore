<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only paper trail of every anonymization or purge run, per-domain.
 * This table is never UPDATEd; the model rejects updates at the application
 * layer. It is the documentary evidence that GDPR Art.17 was honoured for a
 * given subject and data class.
 *
 * @see docs/mil-std-498/SSS.md CAP-DL-004, CAP-DL-005
 * @see docs/mil-std-498/SRS.md DL-F-010, DL-F-013
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anonymization_log_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('data_class', 64);
            $table->string('anonymizer_class', 191);
            $table->unsignedInteger('records_scrubbed_count')->default(0);
            $table->unsignedInteger('records_kept_under_retention_count')->default(0);
            $table->date('retention_until')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('summary')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'data_class']);
            $table->index('data_class');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anonymization_log_entries');
    }
};
