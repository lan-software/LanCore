<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_list_user', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('newsletter_list_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('listmonk_subscriber_id')->nullable();
            $table->string('status')->default('enabled');
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['newsletter_list_id', 'user_id']);
            $table->index('listmonk_subscriber_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_list_user');
    }
};
