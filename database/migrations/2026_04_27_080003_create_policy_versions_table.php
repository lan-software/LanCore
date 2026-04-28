<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policy_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('policy_id')->constrained('policies')->restrictOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('locale', 10);
            $table->longText('content');
            $table->text('public_statement')->nullable();
            $table->boolean('is_non_editorial_change')->default(false);
            $table->string('pdf_path', 255)->nullable();
            $table->timestamp('effective_at');
            $table->timestamp('published_at');
            $table->foreignId('published_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['policy_id', 'locale', 'version_number']);
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_versions');
    }
};
