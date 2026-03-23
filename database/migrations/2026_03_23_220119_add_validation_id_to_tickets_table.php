<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('validation_id', 16)->nullable()->after('status');
        });

        DB::table('tickets')->whereNull('validation_id')->eachById(function ($ticket) {
            DB::table('tickets')
                ->where('id', $ticket->id)
                ->update(['validation_id' => strtoupper(Str::random(16))]);
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->string('validation_id', 16)->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('validation_id');
        });
    }
};
