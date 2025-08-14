<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('taxonomies', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., 'category', 'tag', 'genre'
            $table->boolean('hierarchical')->default(false); // Like categories (true) or tags (false)
            $table->timestamps();
        });
    }
};
