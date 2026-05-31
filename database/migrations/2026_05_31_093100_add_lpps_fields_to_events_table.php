<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->unsignedTinyInteger('attendance_mode')->default(1)->after('seat_capacity');
            $table->string('syndication_status')->default('scheduled')->after('attendance_mode');
            $table->dateTime('previous_start_date')->nullable()->after('syndication_status');
            $table->boolean('has_showers')->nullable()->after('previous_start_date');
            $table->unsignedTinyInteger('sleeping')->default(0)->after('has_showers');
            $table->unsignedTinyInteger('alcohol_policy')->default(0)->after('sleeping');
            $table->unsignedTinyInteger('smoking_policy')->default(0)->after('alcohol_policy');
            $table->unsignedTinyInteger('age_policy')->default(0)->after('smoking_policy');
            $table->unsignedTinyInteger('food_policy')->default(0)->after('age_policy');
            $table->unsignedInteger('network_connection_mbps')->nullable()->after('food_policy');
            $table->unsignedInteger('internet_connection_mbps')->nullable()->after('network_connection_mbps');
            $table->unsignedInteger('wifi_connection_mbps')->nullable()->after('internet_connection_mbps');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->dropColumn([
                'attendance_mode',
                'syndication_status',
                'previous_start_date',
                'has_showers',
                'sleeping',
                'alcohol_policy',
                'smoking_policy',
                'age_policy',
                'food_policy',
                'network_connection_mbps',
                'internet_connection_mbps',
                'wifi_connection_mbps',
            ]);
        });
    }
};
