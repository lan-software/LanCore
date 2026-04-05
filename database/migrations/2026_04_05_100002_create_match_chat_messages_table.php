<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orchestration_job_id')->constrained('orchestration_jobs')->cascadeOnDelete();
            $table->string('steam_id');
            $table->string('player_name');
            $table->text('message');
            $table->boolean('is_team_chat')->default(false);
            $table->timestamp('timestamp');
            $table->timestamp('created_at')->nullable();

            $table->index(['orchestration_job_id', 'timestamp']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_chat_messages');
    }
};
