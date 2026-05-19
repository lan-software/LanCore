<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competition_stage_schedules', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('competition_id')->constrained('competitions')->cascadeOnDelete();
            $table->string('lanbrackets_stage_id');
            $table->string('stage_name');
            $table->string('stage_type');
            $table->unsignedInteger('sequence')->default(1);
            $table->timestamp('starts_at')->nullable();
            $table->unsignedInteger('estimated_duration_minutes')->default(60);
            $table->unsignedInteger('reserve_buffer_minutes')->default(0);
            $table->boolean('duration_overridden')->default(false);
            $table->string('computed_inputs_hash')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['competition_id', 'lanbrackets_stage_id'], 'css_competition_stage_unique');
            $table->index(['competition_id', 'sequence'], 'css_competition_sequence_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_stage_schedules');
    }
};
