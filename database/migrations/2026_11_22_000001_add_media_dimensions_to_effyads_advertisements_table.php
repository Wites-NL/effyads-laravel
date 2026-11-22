<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional image dimensions, so <x-effyads::ad> can set width/height on the
 * image and avoid layout shift while it loads.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('effyads_advertisements', function (Blueprint $table) {
            $table->unsignedInteger('media_width')->nullable()->after('media_url');
            $table->unsignedInteger('media_height')->nullable()->after('media_width');
        });
    }

    public function down(): void
    {
        Schema::table('effyads_advertisements', function (Blueprint $table) {
            $table->dropColumn(['media_width', 'media_height']);
        });
    }
};
