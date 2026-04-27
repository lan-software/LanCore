<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orga_sub_team_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orga_sub_team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['orga_sub_team_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orga_sub_team_memberships');
    }
};
