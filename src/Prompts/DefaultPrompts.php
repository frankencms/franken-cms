<?php

namespace FrankenCms\Prompts;

class DefaultPrompts
{
    /**
     * Get default prompt for SEO Title
     */
    public static function seoTitle(): string
    {
        return 'Generate an SEO-optimized title (50-60 characters) for a blog post.

Title: {title}
Content: {content}

Requirements:
- Must be 50-60 characters
- Include target keywords naturally
- Compelling and click-worthy
- Clear and descriptive

Return only the SEO title, nothing else.';
    }

    /**
     * Get default prompt for SEO Description
     */
    public static function seoDescription(): string
    {
        return 'Generate an SEO meta description (150-160 characters) for:

Title: {title}
Content: {content}

Requirements:
- Must be 150-160 characters
- Summarize main points
- Include call-to-action
- Use active voice

Return only the meta description, nothing else.';
    }

    /**
     * Get default prompt for Post Teaser/Excerpt
     */
    public static function teaser(): string
    {
        return 'Create a compelling teaser/excerpt (2-3 sentences, ~150 characters) for this blog post:

{content}

Requirements:
- Hook the reader
- Summarize key value
- Create curiosity
- 2-3 sentences maximum

Return only the teaser.';
    }

    /**
     * Get default prompt for Image Alt Text
     */
    public static function altText(): string
    {
        return 'Analyze this image and generate descriptive alt text for accessibility.

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
    }

    /**
     * Get default prompt for Image Title
     */
    public static function imageTitle(): string
    {
        return 'Generate a descriptive title for this image (hover text).

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
    }
}
