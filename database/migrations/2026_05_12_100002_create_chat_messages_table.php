<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('room_id')
                ->constrained('chat_rooms')
                ->restrictOnDelete();
            $table->foreignUlid('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->text('body');
            $table->json('mentions_json')->nullable();
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
            $table->foreignUlid('deleted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
        });

        // DESC index on (room_id, created_at) for the room-history pagination
        // pattern (`ORDER BY created_at DESC LIMIT n`). Postgres benefits from
        // a matching DESC index for this access pattern; create explicitly via
        // raw SQL since Blueprint indexes default to ASC.
        DB::statement('CREATE INDEX chat_messages_room_id_created_at_desc_idx ON chat_messages (room_id, created_at DESC)');
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
