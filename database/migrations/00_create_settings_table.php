<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {

        // If the table already exists with the expected columns, consider this migration done.
        if (Schema::hasTable('settings') && Schema::hasColumns('settings', ['group', 'name', 'locked', 'payload'])) {
            return;
        }

        // If the table exists but without the expected shape, don't attempt to create it again.
        if (Schema::hasTable('settings')) {
            return;
        }

        Schema::create('settings', function (Blueprint $table): void {
            $table->id();

            $table->string('group');
            $table->string('name');
            $table->boolean('locked')->default(false);
            $table->json('payload');

            $table->timestamps();

            $table->unique(['group', 'name']);
        });
    }
};
