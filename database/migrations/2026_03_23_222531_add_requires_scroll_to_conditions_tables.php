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
        Schema::table('global_purchase_conditions', function (Blueprint $table) {
            $table->boolean('requires_scroll')->default(false)->after('is_active');
        });

        Schema::table('payment_provider_conditions', function (Blueprint $table) {
            $table->boolean('requires_scroll')->default(false)->after('is_active');
        });

        Schema::table('purchase_requirements', function (Blueprint $table) {
            $table->boolean('requires_scroll')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('global_purchase_conditions', function (Blueprint $table) {
            $table->dropColumn('requires_scroll');
        });

        Schema::table('payment_provider_conditions', function (Blueprint $table) {
            $table->dropColumn('requires_scroll');
        });

        Schema::table('purchase_requirements', function (Blueprint $table) {
            $table->dropColumn('requires_scroll');
        });
    }
};
