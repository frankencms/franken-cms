<?php

use FrankenCms\Prompts\DefaultPrompts;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('cms_ai.image_title_enabled', true);
        $this->migrator->add('cms_ai.image_title_prompt', DefaultPrompts::imageTitle());
    }

    public function down(): void
    {
        $this->migrator->delete('cms_ai.image_title_enabled');
        $this->migrator->delete('cms_ai.image_title_prompt');
    }
};
