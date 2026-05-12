<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('fee_amount')->nullable()->after('total');
            $table->unsignedInteger('net_amount')->nullable()->after('fee_amount');
            $table->string('fee_source', 16)->nullable()->after('net_amount');
            $table->timestamp('fees_fetched_at')->nullable()->after('fee_source');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['fee_amount', 'net_amount', 'fee_source', 'fees_fetched_at']);
        });
    }
};
