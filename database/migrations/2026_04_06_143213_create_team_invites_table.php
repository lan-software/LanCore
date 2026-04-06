<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_invites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('competition_teams')->cascadeOnDelete();
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
            $table->string('email');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('token', 64)->unique();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['team_id', 'email']);
            $table->index('token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_invites');
    }
};
