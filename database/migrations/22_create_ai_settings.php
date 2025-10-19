<?php

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
        $this->migrator->add('cms_ai.seo_title_prompt', 'Generate an SEO-optimized title (50-60 characters) for a blog post.

Title: {title}
Content: {content}

Requirements:
- Must be 50-60 characters
- Include target keywords naturally
- Compelling and click-worthy
- Clear and descriptive

Return only the SEO title, nothing else.');

        // SEO Meta Description Prompt
        $this->migrator->add('cms_ai.seo_description_enabled', true);
        $this->migrator->add('cms_ai.seo_description_prompt', 'Generate an SEO meta description (150-160 characters) for:

Title: {title}
Content: {content}

Requirements:
- Must be 150-160 characters
- Summarize main points
- Include call-to-action
- Use active voice

Return only the meta description, nothing else.');

        // Post Teaser/Excerpt Prompt
        $this->migrator->add('cms_ai.teaser_enabled', true);
        $this->migrator->add('cms_ai.teaser_prompt', 'Create a compelling teaser/excerpt (2-3 sentences, ~150 characters) for this blog post:

{content}

Requirements:
- Hook the reader
- Summarize key value
- Create curiosity
- 2-3 sentences maximum

Return only the teaser.');

        // Image Alt Text Prompt
        $this->migrator->add('cms_ai.alt_text_enabled', true);
        $this->migrator->add('cms_ai.alt_text_prompt', 'Analyze this image and generate descriptive alt text for accessibility.

Additional Context:
Post Title: {title}
Post Content: {content}
Image Filename: {filename}

Requirements:
- Maximum 125 characters
- Describe what you see in the image
- Include relevant details for accessibility
- Be specific and descriptive

Return only the alt text.');
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
