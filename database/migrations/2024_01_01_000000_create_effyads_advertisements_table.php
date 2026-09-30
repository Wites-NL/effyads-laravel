<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('effyads_advertisements', function (Blueprint $table) {
            $table->id();
            $table->string('platform_id')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('link');
            $table->string('media_url')->nullable();
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->json('targeting')->nullable();
            $table->enum('status', ['draft', 'scheduled', 'active', 'paused', 'expired'])->default('draft');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('effyads_advertisements');
    }
};
