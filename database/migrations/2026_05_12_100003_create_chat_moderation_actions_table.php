<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_moderation_actions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('room_id')
                ->constrained('chat_rooms')
                ->cascadeOnDelete();
            $table->foreignId('actor_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('target_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('action', 32);
            $table->text('reason')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['room_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_moderation_actions');
    }
};
