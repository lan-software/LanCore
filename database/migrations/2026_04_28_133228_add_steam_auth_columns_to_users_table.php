<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('steam_id_64', 20)->nullable()->unique()->after('email');
            $table->timestamp('steam_linked_at')->nullable()->after('steam_id_64');
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['steam_id_64']);
            $table->dropColumn(['steam_id_64', 'steam_linked_at']);
            $table->string('password')->nullable(false)->change();
        });
    }
};
