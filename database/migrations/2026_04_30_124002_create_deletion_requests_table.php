<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The deletion request state machine. Captures both user-initiated and
 * admin-initiated deletions; lifecycle: pending_email_confirm → pending_grace
 * → anonymized | cancelled | force_deleted.
 *
 * @see docs/mil-std-498/SSS.md CAP-DL-001, CAP-DL-002, CAP-DL-003
 * @see docs/mil-std-498/SRS.md DL-F-001, DL-F-002, DL-F-005
 * @see docs/mil-std-498/SSDD.md §5.11 "Deletion & Retention Pipeline"
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deletion_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('initiator', 16);
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('requested_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32);
            $table->text('reason')->nullable();
            $table->char('email_confirmation_token', 64)->nullable()->unique();
            $table->timestamp('email_confirmed_at')->nullable();
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('anonymized_at')->nullable();
            $table->timestamp('force_deleted_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_for'], 'deletion_requests_status_scheduled_idx');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deletion_requests');
    }
};
