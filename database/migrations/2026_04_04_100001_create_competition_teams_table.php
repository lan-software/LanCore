<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competition_teams', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('competition_id')->constrained('competitions')->cascadeOnDelete();
            $table->string('name');
            $table->string('tag', 10)->nullable();
            $table->foreignUlid('captain_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->ulid('lanbrackets_id')->nullable()->index();
            $table->timestamps();

            $table->unique(['competition_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_teams');
    }
};
