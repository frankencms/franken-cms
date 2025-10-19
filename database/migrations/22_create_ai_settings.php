<?php

use FrankenCms\Prompts\DefaultPrompts;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('cms_ai.enabled', false);
        $this->migrator->add('cms_ai.provider', 'openai');
        $this->migrator->add('cms_ai.api_key', null);
        $this->migrator->add('cms_ai.model', 'gpt-4o');

        // SEO Title Generator Prompt
        $this->migrator->add('cms_ai.seo_title_enabled', true);
        $this->migrator->add('cms_ai.seo_title_prompt', DefaultPrompts::seoTitle());

        // SEO Meta Description Prompt
        $this->migrator->add('cms_ai.seo_description_enabled', true);
        $this->migrator->add('cms_ai.seo_description_prompt', DefaultPrompts::seoDescription());

        // Post Teaser/Excerpt Prompt
        $this->migrator->add('cms_ai.teaser_enabled', true);
        $this->migrator->add('cms_ai.teaser_prompt', DefaultPrompts::teaser());

        // Image Alt Text Prompt
        $this->migrator->add('cms_ai.alt_text_enabled', true);
        $this->migrator->add('cms_ai.alt_text_prompt', DefaultPrompts::altText());
    }

    public function down(): void
    {
        $this->migrator->delete('cms_ai.enabled');
        $this->migrator->delete('cms_ai.provider');
        $this->migrator->delete('cms_ai.api_key');
        $this->migrator->delete('cms_ai.model');

        // Delete prompt settings
        $this->migrator->delete('cms_ai.seo_title_enabled');
        $this->migrator->delete('cms_ai.seo_title_prompt');
        $this->migrator->delete('cms_ai.seo_description_enabled');
        $this->migrator->delete('cms_ai.seo_description_prompt');
        $this->migrator->delete('cms_ai.teaser_enabled');
        $this->migrator->delete('cms_ai.teaser_prompt');
        $this->migrator->delete('cms_ai.alt_text_enabled');
        $this->migrator->delete('cms_ai.alt_text_prompt');
    }
};
