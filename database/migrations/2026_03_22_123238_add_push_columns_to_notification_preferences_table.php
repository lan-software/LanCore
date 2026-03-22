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
        Schema::table('notification_preferences', function (Blueprint $table) {
            $table->boolean('push_on_news')->default(false)->after('mail_on_news');
            $table->boolean('push_on_events')->default(false)->after('mail_on_events');
            $table->boolean('push_on_news_comments')->default(false)->after('mail_on_news_comments');
            $table->boolean('push_on_program_time_slots')->default(false)->after('mail_on_program_time_slots');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table) {
            $table->dropColumn([
                'push_on_news',
                'push_on_events',
                'push_on_news_comments',
                'push_on_program_time_slots',
            ]);
        });
    }
};
