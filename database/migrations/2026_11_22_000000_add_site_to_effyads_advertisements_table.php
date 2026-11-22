<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a "site" column so one Laravel app can serve several EffyAds websites
 * (multi-tenant). Single-site installs keep site = '' (not NULL: NULLs are
 * never equal in a unique index, which would allow duplicate platform_ids).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('effyads_advertisements', function (Blueprint $table) {
            $table->string('site')->default('')->after('id');
        });

        Schema::table('effyads_advertisements', function (Blueprint $table) {
            $table->dropUnique(['platform_id']);
            $table->unique(['site', 'platform_id']);
            $table->index('site');
        });
    }

    public function down(): void
    {
        Schema::table('effyads_advertisements', function (Blueprint $table) {
            $table->dropUnique(['site', 'platform_id']);
            $table->dropIndex(['site']);
            $table->unique('platform_id');
        });

        Schema::table('effyads_advertisements', function (Blueprint $table) {
            $table->dropColumn('site');
        });
    }
};
