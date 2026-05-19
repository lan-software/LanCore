<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orchestration_jobs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('game_server_id')->nullable()->constrained('game_servers')->nullOnDelete();
            $table->foreignUlid('competition_id')->constrained('competitions')->cascadeOnDelete();
            $table->ulid('lanbrackets_match_id');
            $table->foreignUlid('game_id')->constrained('games')->cascadeOnDelete();
            $table->foreignUlid('game_mode_id')->nullable()->constrained('game_modes')->nullOnDelete();
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
