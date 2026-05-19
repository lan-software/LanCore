<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competition_round_schedules', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('stage_schedule_id')
                ->constrained('competition_stage_schedules')
                ->cascadeOnDelete();
            $table->unsignedInteger('lanbrackets_round_number');
            $table->unsignedInteger('sequence')->default(1);
            $table->string('label')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->unsignedInteger('estimated_duration_minutes')->default(30);
            $table->unsignedInteger('reserve_buffer_minutes')->default(10);
            $table->boolean('duration_overridden')->default(false);
            $table->string('computed_inputs_hash')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['stage_schedule_id', 'lanbrackets_round_number'], 'crs_stage_round_unique');
            $table->index(['stage_schedule_id', 'sequence'], 'crs_stage_sequence_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_round_schedules');
    }
};
