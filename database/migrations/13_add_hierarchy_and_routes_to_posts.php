<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // Add parent_id for page hierarchy
            $table->foreignId('parent_id')
                ->nullable()
                ->after('post_type')
                ->constrained('posts')
                ->onDelete('cascade');

            // Add route_name for named routes (optional, unique)
            $table->string('route_name')
                ->nullable()
                ->unique()
                ->after('post_slug');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
            $table->dropUnique(['route_name']);
            $table->dropColumn('route_name');
        });
    }
};
