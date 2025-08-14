<?php

use FrankenCms\Enums\PostStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_author_id')->nullable();
            $table->string('post_title');
            $table->string('post_slug');
            $table->json('post_content')->nullable();
            $table->string('post_status')->default(PostStatus::DRAFT->value);
            $table->timestamp('post_published_at')->nullable();
            $table->string('post_password')->nullable();
            $table->string('post_type');
            $table->unsignedBigInteger('post_parent')->default(0);
            $table->timestamps();
        });

        Schema::create('postmeta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')
                ->constrained()
                ->onDelete('cascade');
            //            $table->foreign('post_id')
            //                ->references('id')
            //                ->on('posts')
            //                ->onDelete('cascade');
            $table->string('meta_key')->unique();
            $table->json('meta_value');
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
