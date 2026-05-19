<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_lists', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedBigInteger('listmonk_id')->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('type')->default('private');
            $table->string('optin')->default('single');
            $table->json('tags')->nullable();
            $table->boolean('is_user_selectable')->default(false);
            $table->boolean('is_default_public')->default(false);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_lists');
    }
};
