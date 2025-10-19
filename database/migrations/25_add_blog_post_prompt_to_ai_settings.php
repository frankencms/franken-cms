<?php

use FrankenCms\Prompts\DefaultPrompts;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('cms_ai.blog_post_enabled', true);
        $this->migrator->add('cms_ai.blog_post_prompt', DefaultPrompts::blogPost());
    }

    public function down(): void
    {
        $this->migrator->delete('cms_ai.blog_post_enabled');
        $this->migrator->delete('cms_ai.blog_post_prompt');
    }
};
