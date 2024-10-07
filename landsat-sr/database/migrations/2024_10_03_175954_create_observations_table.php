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
        Schema::create('observations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('description')->nullable();
            $table->float('latitude');
            $table->float('longitude');
            $table->string('place');
            $table->datetime('overpass_time');
            $table->string('status');
            $table->float('cloud_cover')->nullable();
            $table->string('satellite');
            $table->boolean('only_sunlit')->default(true);
            $table->json('metadata')->nullable();
            $table->tinytext('tags')->nullable();
            $table->string('channel')->nullable();
            $table->datetime('lead_time')->nullable();
            $table->json('recipients')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('observations');
    }
};
