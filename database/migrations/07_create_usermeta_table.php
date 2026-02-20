<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usermeta', function (Blueprint $table) {
            $table->id();
            // Assuming you have a users table and want to enforce referential integrity
            $table->unsignedBigInteger('user_id');
            $table->string('meta_key');
            $table->json('meta_value');
            $table->timestamps();

            // Create a foreign key constraint linking to the users table.
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
