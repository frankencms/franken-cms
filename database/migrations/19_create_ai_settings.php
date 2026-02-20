<?php

use FrankenCms\Prompts\DefaultPrompts;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('franken_cms_ai.enabled', false);
        $this->migrator->add('franken_cms_ai.provider', 'openai');
        $this->migrator->add('franken_cms_ai.api_key', null);
        $this->migrator->add('franken_cms_ai.model', 'gpt-4o');

        // SEO Title Generator Prompt
        $this->migrator->add('franken_cms_ai.seo_title_enabled', true);
        $this->migrator->add('franken_cms_ai.seo_title_prompt', DefaultPrompts::seoTitle());

        // SEO Meta Description Prompt
        $this->migrator->add('franken_cms_ai.seo_description_enabled', true);
        $this->migrator->add('franken_cms_ai.seo_description_prompt', DefaultPrompts::seoDescription());

        // Post Teaser/Excerpt Prompt
        $this->migrator->add('franken_cms_ai.teaser_enabled', true);
        $this->migrator->add('franken_cms_ai.teaser_prompt', DefaultPrompts::teaser());

        // Image Alt Text Prompt
        $this->migrator->add('franken_cms_ai.alt_text_enabled', true);
        $this->migrator->add('franken_cms_ai.alt_text_prompt', DefaultPrompts::altText());

        // Image Title Prompt
        $this->migrator->add('franken_cms_ai.image_title_enabled', true);
        $this->migrator->add('franken_cms_ai.image_title_prompt', DefaultPrompts::imageTitle());

        // Blog Post Generator Prompt
        $this->migrator->add('franken_cms_ai.blog_post_enabled', true);
        $this->migrator->add('franken_cms_ai.blog_post_prompt', DefaultPrompts::blogPost());

        // Blog Post Title Generator Prompt
        $this->migrator->add('franken_cms_ai.blog_post_title_enabled', true);
        $this->migrator->add('franken_cms_ai.blog_post_title_prompt', DefaultPrompts::blogPostTitle());
    }
};
