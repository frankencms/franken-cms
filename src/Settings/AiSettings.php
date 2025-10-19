<?php

namespace FrankenCms\Settings;

use FrankenCms\SettingsCasts\EncryptedSettingsCast;
use Spatie\LaravelSettings\Settings;

class AiSettings extends Settings
{
    // Provider Configuration
    public bool $enabled = false;

    public string $provider = 'openai';

    public ?string $api_key = null;

    public string $model = 'gpt-4o';

    // SEO Title Generator Prompt
    public bool $seo_title_enabled = true;

    public string $seo_title_prompt = 'Generate an SEO-optimized title (50-60 characters) for a blog post.

Title: {title}
Content: {content}

Requirements:
- Must be 50-60 characters
- Include target keywords naturally
- Compelling and click-worthy
- Clear and descriptive

Return only the SEO title, nothing else.';

    // SEO Meta Description Prompt
    public bool $seo_description_enabled = true;

    public string $seo_description_prompt = 'Generate an SEO meta description (150-160 characters) for:

Title: {title}
Content: {content}

Requirements:
- Must be 150-160 characters
- Summarize main points
- Include call-to-action
- Use active voice

Return only the meta description, nothing else.';

    // Post Teaser/Excerpt Prompt
    public bool $teaser_enabled = true;

    public string $teaser_prompt = 'Create a compelling teaser/excerpt (2-3 sentences, ~150 characters) for this blog post:

{content}

Requirements:
- Hook the reader
- Summarize key value
- Create curiosity
- 2-3 sentences maximum

Return only the teaser.';

    // Image Alt Text Prompt
    public bool $alt_text_enabled = true;

    public string $alt_text_prompt = 'Analyze this image and generate descriptive alt text for accessibility.

Additional Context:
Post Title: {title}
Post Content: {content}
Image Filename: {filename}

Requirements:
- Maximum 125 characters
- Describe what you see in the image
- Include relevant details for accessibility
- Be specific and descriptive

Return only the alt text.';

    // Image Title Prompt
    public bool $image_title_enabled = true;

    public string $image_title_prompt = 'Generate a descriptive title for this image (hover text).

Additional Context:
Post Title: {title}
Post Content: {content}
Image Filename: {filename}

Requirements:
- Short and descriptive (3-8 words)
- Provides additional context when hovering
- Clear and informative
- Professional tone

Return only the image title.';

    public static function group(): string
    {
        return 'cms_ai';
    }

    /**
     * Define encrypted fields
     */
    public static function casts(): array
    {
        return [
            'api_key' => EncryptedSettingsCast::class,
        ];
    }
}
