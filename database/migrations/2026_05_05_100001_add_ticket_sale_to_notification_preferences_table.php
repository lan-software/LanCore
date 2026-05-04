<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table) {
            $table->boolean('mail_on_ticket_sale')->default(true);
            $table->boolean('push_on_ticket_sale')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table) {
            $table->dropColumn(['mail_on_ticket_sale', 'push_on_ticket_sale']);
        });
    }
};
