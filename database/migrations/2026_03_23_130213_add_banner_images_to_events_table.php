<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->json('banner_images')->nullable()->after('banner_image');
        });

        // Migrate existing single banner_image values into the new banner_images array.
        DB::table('events')
            ->whereNotNull('banner_image')
            ->lazyById()
            ->each(function (object $event) {
                DB::table('events')
                    ->where('id', $event->id)
                    ->update(['banner_images' => json_encode([$event->banner_image])]);
            });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('banner_image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('banner_image')->nullable()->after('banner_images');
        });

        // Restore the first image from banner_images back to banner_image.
        DB::table('events')
            ->whereNotNull('banner_images')
            ->lazyById()
            ->each(function (object $event) {
                $images = json_decode($event->banner_images, true);
                DB::table('events')
                    ->where('id', $event->id)
                    ->update(['banner_image' => $images[0] ?? null]);
            });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('banner_images');
        });
    }
};
