<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seat_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->jsonb('data')->default(new \Illuminate\Database\Query\Expression('(JSON_OBJECT(\'blocks\', JSON_ARRAY()))'));
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seat_plans');
    }
};
