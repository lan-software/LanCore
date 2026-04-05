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
        Schema::table('integration_apps', function (Blueprint $table) {
            $table->boolean('send_announcements')->default(false)->after('is_active');
            $table->string('announcement_endpoint')->nullable()->after('send_announcements');
        });

        Schema::table('webhooks', function (Blueprint $table) {
            $table->foreignId('integration_app_id')->nullable()->after('id')->constrained('integration_apps')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('webhooks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('integration_app_id');
        });

        Schema::table('integration_apps', function (Blueprint $table) {
            $table->dropColumn(['send_announcements', 'announcement_endpoint']);
        });
    }
};
