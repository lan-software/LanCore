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
            $table->string('nav_url')->nullable()->after('callback_url');
            $table->string('nav_icon')->nullable()->after('nav_url');
            $table->string('nav_label')->nullable()->after('nav_icon');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('integration_apps', function (Blueprint $table) {
            $table->dropColumn(['nav_url', 'nav_icon', 'nav_label']);
        });
    }
};
