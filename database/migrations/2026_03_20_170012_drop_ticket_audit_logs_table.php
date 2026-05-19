<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('ticket_audit_logs');
    }

    public function down(): void
    {
        Schema::create('ticket_audit_logs', function ($table) {
            $table->ulid('id')->primary();
            $table->string('action');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->foreignUlid('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }
};
