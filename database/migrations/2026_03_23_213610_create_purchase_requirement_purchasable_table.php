<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_requirement_purchasable', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_requirement_id')->constrained()->cascadeOnDelete();
            $table->string('purchasable_type');
            $table->unsignedBigInteger('purchasable_id');
            $table->timestamps();

            $table->unique(['purchase_requirement_id', 'purchasable_type', 'purchasable_id'], 'pr_purchasable_unique');
            $table->index(['purchasable_type', 'purchasable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_requirement_purchasable');
    }
};
