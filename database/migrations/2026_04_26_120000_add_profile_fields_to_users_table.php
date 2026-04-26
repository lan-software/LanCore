<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @see docs/mil-std-498/SRS.md USR-F-022, USR-F-024, USR-F-025
 * @see docs/mil-std-498/DBDD.md §4.1.1 users
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('username', 32)->nullable()->after('email');
            $table->string('short_bio', 160)->nullable();
            $table->text('profile_description')->nullable();
            $table->string('profile_emoji', 16)->nullable();
            $table->string('avatar_source', 16)->default('default');
            $table->string('avatar_path')->nullable();
            $table->string('banner_path')->nullable();
            $table->string('profile_visibility', 16)->default('logged_in');
            $table->timestamp('profile_updated_at')->nullable();

            $table->unique('username');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['username']);
            $table->dropColumn([
                'username',
                'short_bio',
                'profile_description',
                'profile_emoji',
                'avatar_source',
                'avatar_path',
                'banner_path',
                'profile_visibility',
                'profile_updated_at',
            ]);
        });
    }
};
