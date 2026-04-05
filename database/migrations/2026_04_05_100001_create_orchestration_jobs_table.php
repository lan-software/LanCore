<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orchestration_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_server_id')->nullable()->constrained('game_servers')->nullOnDelete();
            $table->foreignId('competition_id')->constrained('competitions')->cascadeOnDelete();
            $table->unsignedBigInteger('lanbrackets_match_id');
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->foreignId('game_mode_id')->nullable()->constrained('game_modes')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->json('match_config')->nullable();
            $table->string('match_handler')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['competition_id', 'lanbrackets_match_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orchestration_jobs');
    }
};
