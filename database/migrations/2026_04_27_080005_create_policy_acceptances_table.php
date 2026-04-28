<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policy_acceptances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('policy_version_id')->constrained('policy_versions')->restrictOnDelete();
            $table->timestamp('accepted_at');
            $table->string('locale', 10);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('source', 32);
            $table->timestamp('withdrawn_at')->nullable();
            $table->text('withdrawn_reason')->nullable();
            $table->string('withdrawn_ip', 45)->nullable();
            $table->string('withdrawn_user_agent', 512)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'policy_version_id']);
            $table->index(['user_id', 'withdrawn_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_acceptances');
    }
};
