<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_result_proofs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained('competitions')->cascadeOnDelete();
            $table->unsignedBigInteger('lanbrackets_match_id')->index();
            $table->foreignId('submitted_by_user_id')->constrained('users');
            $table->foreignId('submitted_by_team_id')->nullable()->constrained('competition_teams');
            $table->string('screenshot_path');
            $table->json('scores');
            $table->boolean('is_disputed')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_result_proofs');
    }
};
