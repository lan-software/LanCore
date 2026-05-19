<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_rooms', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('key', 191)->unique();
            $table->string('title', 191)->nullable();
            $table->string('status', 32)->default('open');
            $table->string('consumer_domain', 64);
            $table->string('policy_class', 191);
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('write_locked_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->foreignUlid('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['consumer_domain', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_rooms');
    }
};
