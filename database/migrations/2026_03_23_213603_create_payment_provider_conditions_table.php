<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_provider_conditions', function (Blueprint $table) {
            $table->id();
            $table->string('payment_method');
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('content')->nullable();
            $table->string('acknowledgement_label');
            $table->boolean('is_required')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_provider_conditions');
    }
};
