<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table): void {
            $table->boolean('mail_on_chat_mention')->default(true)->after('mail_on_ticket_sale');
            $table->boolean('push_on_chat_mention')->default(false)->after('push_on_ticket_sale');
        });
    }

    public function down(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table): void {
            $table->dropColumn(['mail_on_chat_mention', 'push_on_chat_mention']);
        });
    }
};
