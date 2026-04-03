<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_types', function (Blueprint $table) {
            $table->unsignedInteger('max_users_per_ticket')->default(1)->after('seats_per_ticket');
            $table->string('check_in_mode')->default('individual')->after('max_users_per_ticket');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_types', function (Blueprint $table) {
            $table->dropColumn(['max_users_per_ticket', 'check_in_mode']);
        });
    }
};
