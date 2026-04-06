<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entrance_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->nullable()->constrained()->nullOnDelete();
            $table->string('validation_id', 32)->nullable();
            $table->string('action', 30);
            $table->string('decision', 30)->nullable();
            $table->unsignedBigInteger('operator_id');
            $table->string('operator_session')->nullable();
            $table->text('client_info')->nullable();
            $table->text('override_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['ticket_id', 'action']);
            $table->index('validation_id');
            $table->index('operator_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entrance_audit_logs');
    }
};
