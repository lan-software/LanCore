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
        Schema::create('checkout_acknowledgements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('acknowledgeable_type');
            $table->unsignedBigInteger('acknowledgeable_id');
            $table->string('acknowledgement_key')->nullable();
            $table->timestamp('acknowledged_at');
            $table->timestamps();

            $table->index(['acknowledgeable_type', 'acknowledgeable_id'], 'ack_morph_index');
            $table->index(['user_id', 'acknowledgeable_type', 'acknowledgeable_id'], 'ack_user_morph_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checkout_acknowledgements');
    }
};
