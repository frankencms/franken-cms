<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->foreignId('taxonomy_id')->constrained()->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('terms')->onDelete('cascade');
            $table->timestamps();

            // Unique constraint: slug must be unique within each taxonomy
            // This allows "news" as both a category and a tag
            $table->unique(['taxonomy_id', 'slug']);
        });
    }
};
