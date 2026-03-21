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
        Schema::create('ticket_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('price');
            $table->unsignedInteger('quota');
            $table->unsignedInteger('seats_per_ticket')->default(1);
            $table->boolean('is_row_ticket')->default(false);
            $table->boolean('is_seatable')->default(true);
            $table->boolean('is_hidden')->default(false);
            $table->dateTime('purchase_from')->nullable();
            $table->dateTime('purchase_until')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ticket_group_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_types');
    }
};
