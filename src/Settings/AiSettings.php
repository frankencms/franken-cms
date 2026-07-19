<?php

namespace FrankenCms\Settings;

use Spatie\LaravelSettings\Settings;

class AiSettings extends Settings
{
    // Provider Configuration
    public bool $enabled = false;

    public string $provider = 'openai';

    public string $model = 'gpt-4o';

    // SEO Title Generator Prompt
    public bool $seo_title_enabled = true;

    public string $seo_title_prompt = '';

    // SEO Meta Description Prompt
    public bool $seo_description_enabled = true;

    public string $seo_description_prompt = '';

    // Post Teaser/Excerpt Prompt
    public bool $teaser_enabled = true;

    public string $teaser_prompt = '';

    // Image Alt Text Prompt
    public bool $alt_text_enabled = true;

    public string $alt_text_prompt = '';

    // Image Title Prompt
    public bool $image_title_enabled = true;

    public string $image_title_prompt = '';

    // Blog Post Generator Prompt
    public bool $blog_post_enabled = true;

    public string $blog_post_prompt = '';

    // Blog Post Title Generator Prompt
    public bool $blog_post_title_enabled = true;

    public string $blog_post_title_prompt = '';

    // Featured Image Generation
    public bool $featured_image_enabled = true;

    public string $featured_image_prompt = '';

    public string $featured_image_quality = 'medium';

    public ?string $featured_image_provider = null;

    public ?string $featured_image_model = null;

    public static function group(): string
    {
        return 'franken_cms_ai';
    }
}
