<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Introduce normalized seating tables: blocks → rows → seats, plus labels and
 * a block ↔ ticket_category pivot for SET-F-011 restrictions. `seat_plans`
 * gains a `background_image_url`. `seat_assignments` gains a nullable
 * `seat_plan_seat_id` FK; the legacy string `seat_id` column stays for
 * backfill. Columns and the FK are tightened by the follow-up contract
 * migration after the backfill runs.
 *
 * @see docs/mil-std-498/SRS.md SET-F-002, SET-F-006, SET-F-011
 * @see docs/mil-std-498/DBDD.md §4.5 Seating
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seat_plans', function (Blueprint $table): void {
            $table->string('background_image_url', 2048)->nullable()->after('event_id');
        });

        Schema::create('seat_plan_blocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('seat_plan_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('color', 16)->default('#2c3e50');
            $table->string('background_image_url', 2048)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['seat_plan_id', 'sort_order']);
        });

        Schema::create('seat_plan_rows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('seat_plan_block_id')->constrained()->cascadeOnDelete();
            $table->string('name', 64);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['seat_plan_block_id', 'name']);
        });

        Schema::create('seat_plan_seats', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('seat_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seat_plan_block_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seat_plan_row_id')->nullable()->constrained('seat_plan_rows')->nullOnDelete();
            $table->unsignedInteger('number')->nullable();
            $table->string('title', 64);
            $table->integer('x');
            $table->integer('y');
            $table->boolean('salable')->default(true);
            $table->string('color', 16)->nullable();
            $table->text('note')->nullable();
            $table->jsonb('custom_data')->nullable();
            $table->timestamps();

            $table->unique(['seat_plan_block_id', 'seat_plan_row_id', 'number']);
            $table->index(['seat_plan_id', 'salable']);
        });

        Schema::create('seat_plan_labels', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('seat_plan_block_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->integer('x');
            $table->integer('y');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('seat_plan_block_category_restrictions', function (Blueprint $table): void {
            $table->foreignId('seat_plan_block_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_category_id')->constrained()->cascadeOnDelete();

            $table->primary(['seat_plan_block_id', 'ticket_category_id']);
        });

        Schema::table('seat_assignments', function (Blueprint $table): void {
            $table->foreignId('seat_plan_seat_id')
                ->nullable()
                ->after('seat_plan_id')
                ->constrained('seat_plan_seats')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('seat_assignments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('seat_plan_seat_id');
        });

        Schema::dropIfExists('seat_plan_block_category_restrictions');
        Schema::dropIfExists('seat_plan_labels');
        Schema::dropIfExists('seat_plan_seats');
        Schema::dropIfExists('seat_plan_rows');
        Schema::dropIfExists('seat_plan_blocks');

        Schema::table('seat_plans', function (Blueprint $table): void {
            $table->dropColumn('background_image_url');
        });
    }
};
