<?php

use FrankenCms\Prompts\DefaultPrompts;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('franken_cms_ai.featured_image_enabled', true);
        $this->migrator->add('franken_cms_ai.featured_image_prompt', DefaultPrompts::featuredImage());
        $this->migrator->add('franken_cms_ai.featured_image_quality', 'medium');
        $this->migrator->add('franken_cms_ai.featured_image_provider', null);
        $this->migrator->add('franken_cms_ai.featured_image_model', null);
    }
};
