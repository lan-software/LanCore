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
            $table->boolean('send_role_updates')->default(false)->after('announcement_endpoint');
            $table->string('roles_endpoint')->nullable()->after('send_role_updates');
        });
    }

    public function down(): void
    {
        Schema::table('integration_apps', function (Blueprint $table) {
            $table->dropColumn(['send_role_updates', 'roles_endpoint']);
        });
    }
};
