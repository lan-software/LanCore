<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->foreignId('game_id')->nullable()->constrained('games')->nullOnDelete();
            $table->foreignId('game_mode_id')->nullable()->constrained('game_modes')->nullOnDelete();
            $table->string('type');
            $table->string('stage_type');
            $table->string('status')->default('draft');
            $table->unsignedInteger('team_size')->nullable();
            $table->unsignedInteger('max_teams')->nullable();
            $table->timestamp('registration_opens_at')->nullable();
            $table->timestamp('registration_closes_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedBigInteger('lanbrackets_id')->nullable()->index();
            $table->string('lanbrackets_share_token')->nullable();
            $table->json('settings')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitions');
    }
};
