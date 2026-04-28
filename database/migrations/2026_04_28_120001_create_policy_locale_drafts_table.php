<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policy_locale_drafts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('policy_id')->constrained('policies')->restrictOnDelete();
            $table->string('locale', 10);
            $table->longText('content')->nullable();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['policy_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_locale_drafts');
    }
};
