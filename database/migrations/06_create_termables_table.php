<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('termables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('term_id')->constrained()->onDelete('cascade');
            $table->morphs('termable'); // Allows linking terms to multiple models
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('termables');
    }
};
