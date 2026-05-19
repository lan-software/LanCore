<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_servers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('host');
            $table->unsignedInteger('port');
            $table->foreignUlid('game_id')->constrained('games')->cascadeOnDelete();
            $table->foreignUlid('game_mode_id')->nullable()->constrained('game_modes')->nullOnDelete();
            $table->string('status')->default('available');
            $table->string('allocation_type');
            $table->text('credentials')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['game_id', 'status', 'allocation_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_servers');
    }
};
