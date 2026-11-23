<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The IAB format an ad is made for (e.g. "medium-rectangle"), so
 * <x-effyads::ad format="..."> only shows ads that fit the slot.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('effyads_advertisements', function (Blueprint $table) {
            $table->string('format', 64)->nullable()->after('link');
        });
    }

    public function down(): void
    {
        Schema::table('effyads_advertisements', function (Blueprint $table) {
            $table->dropColumn('format');
        });
    }
};
