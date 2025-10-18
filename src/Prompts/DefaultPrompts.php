<?php

namespace FrankenCms\Prompts;

class DefaultPrompts
{
    /**
     * Get all default prompt templates
     */
    public static function all(): array
    {
        return [
            [
                'label'      => 'SEO Title Generator',
                'action_key' => 'generate_seo_title',
                'context'    => 'all',
                'prompt'     => 'Generate an SEO-optimized title (50-60 characters) for a blog post.

Title: {title}
Content: {content}

Requirements:
- Must be 50-60 characters
- Include target keywords naturally
- Compelling and click-worthy
- Clear and descriptive

Return only the SEO title, nothing else.',
                'max_tokens'  => 100,
                'temperature' => 0.7,
                'enabled'     => true,
            ],

            [
                'label'      => 'SEO Meta Description',
                'action_key' => 'generate_seo_description',
                'context'    => 'all',
                'prompt'     => 'Generate an SEO meta description (150-160 characters) for:

Title: {title}
Content: {content}

Requirements:
- Must be 150-160 characters
- Summarize main points
- Include call-to-action
- Use active voice

Return only the meta description, nothing else.',
                'max_tokens'  => 150,
                'temperature' => 0.7,
                'enabled'     => true,
            ],

            [
                'label'      => 'Post Teaser/Excerpt',
                'action_key' => 'generate_teaser',
                'context'    => 'post',
                'prompt'     => 'Create a compelling teaser/excerpt (2-3 sentences, ~150 characters) for this blog post:

{content}

Requirements:
- Hook the reader
- Summarize key value
- Create curiosity
- 2-3 sentences maximum

Return only the teaser.',
                'max_tokens'  => 200,
                'temperature' => 0.8,
                'enabled'     => true,
            ],

            [
                'label'      => 'Image Alt Text',
                'action_key' => 'generate_alt_text',
                'context'    => 'media',
                'prompt'     => 'Generate descriptive alt text for accessibility based on this context:

Post Title: {title}
Post Content: {content}
Image Filename: {filename}

Requirements:
- Maximum 125 characters
- Describe image content
- Provide context
- Be specific and descriptive

Return only the alt text.',
                'max_tokens'  => 100,
                'temperature' => 0.5,
                'enabled'     => true,
            ],
        ];
    }
}
