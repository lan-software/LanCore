<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('integration_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integration_app_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->string('plain_text_prefix', 8);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index('integration_app_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integration_tokens');
    }
};
