<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-editable per-data-class retention windows. Defaults are seeded by
 * RetentionPolicySeeder. `can_be_force_deleted` lets us pin policies (e.g.
 * legal-hold scenarios) so even ForceDeleteUserData refuses to purge them.
 *
 * @see docs/mil-std-498/SSS.md CAP-DL-005
 * @see docs/mil-std-498/SRS.md DL-F-011, DL-F-012
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retention_policies', function (Blueprint $table): void {
            $table->id();
            $table->string('data_class', 64)->unique();
            $table->unsignedInteger('retention_days');
            $table->text('legal_basis');
            $table->boolean('can_be_force_deleted')->default(true);
            $table->text('description')->nullable();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retention_policies');
    }
};
