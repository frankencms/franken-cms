<?php

use FrankenCms\Models\Taxonomy;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Create default category taxonomy
        Taxonomy::firstOrCreate(
            ['name' => 'category'],
            ['hierarchical' => true]
        );

        // Create default tag taxonomy
        Taxonomy::firstOrCreate(
            ['name' => 'tag'],
            ['hierarchical' => false]
        );
    }

    public function down(): void
    {
        Taxonomy::whereIn('name', ['category', 'tag'])->delete();
    }
};
