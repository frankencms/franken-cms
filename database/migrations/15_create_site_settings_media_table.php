<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings_media', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        // Create the singleton instance
        DB::table('site_settings_media')->insert([
            'id'         => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings_media');
    }
};
