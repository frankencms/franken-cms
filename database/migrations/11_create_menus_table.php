<?php

use FrankenCms\Enums\LinkTargets;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['slug', 'is_active']);
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->onDelete('cascade');
            $table->string('label');
            $table->text('url')->nullable();
            $table->string('route_name')->nullable();
            $table->json('route_parameters')->nullable();
            $table->string('target')->default(LinkTargets::_SELF);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            // Polymorphic relationship for linking to models (Post, Page, etc.)
            $table->string('linkable_type')->nullable();
            $table->unsignedBigInteger('linkable_id')->nullable();

            // Additional data as JSON for extensibility
            $table->json('additional_data')->nullable();

            $table->timestamps();

            $table->index(['menu_id', 'parent_id', 'sort_order']);
            $table->index(['linkable_type', 'linkable_id']);
            $table->index(['is_active']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
