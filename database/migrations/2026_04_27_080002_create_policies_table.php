<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('policy_type_id')->constrained('policy_types')->restrictOnDelete();
            $table->string('key', 64)->unique();
            $table->string('name', 128);
            $table->text('description')->nullable();
            $table->boolean('is_required_for_registration')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index(['is_required_for_registration', 'archived_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policies');
    }
};
