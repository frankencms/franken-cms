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
        Schema::create('user_bios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable(); // Job title, e.g., "Senior Developer"
            $table->text('bio')->nullable(); // The biography/about text
            $table->string('website')->nullable();
            $table->json('social_links')->nullable(); // Store social media links as JSON
            $table->timestamps();

            // Ensure one bio per user
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_bios');
    }
};
