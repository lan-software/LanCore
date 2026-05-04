<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_types', function (Blueprint $table) {
            $table->boolean('notify_on_release')->default(false);
            $table->boolean('notify_on_end')->default(false);
            $table->unsignedInteger('notify_on_end_lead_minutes')->default(1440);
            $table->timestamp('release_notified_at')->nullable();
            $table->timestamp('end_notified_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ticket_types', function (Blueprint $table) {
            $table->dropColumn([
                'notify_on_release',
                'notify_on_end',
                'notify_on_end_lead_minutes',
                'release_notified_at',
                'end_notified_at',
            ]);
        });
    }
};
