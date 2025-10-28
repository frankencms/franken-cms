<?php

declare(strict_types=1);

namespace FrankenCms\Services;

use FrankenCms\Models\Post;
use FrankenCms\Models\SiteSettingsMedia;
use FrankenCms\Settings\SeoSettings;

class SeoService
{
    public function __construct(
        protected SeoSettings $settings
    ) {}

    /**
     * Get the SEO title for a post/page
     */
    public function getTitle(?Post $post = null, ?string $suffix = null): string
    {
        // Get the base title (custom SEO title, post title, or site name)
        $title = $post?->getMeta('seo_title') ?? $post?->post_title ?? $this->settings->site_name;

        // Check if we should append site name
        if (! $this->settings->append_site_name) {
            // User disabled site name appending, just return the title
            return $title;
        }

        // If the title is already the site name (no post or post has no title), don't duplicate it
        if ($title === $this->settings->site_name) {
            return $title;
        }

        // Get the site name and separator
        $siteName = $suffix ?? $this->settings->site_name;
        $separator = $this->settings->title_separator;

        // Construct the title based on position preference
        if ($this->settings->site_name_position === 'prepend') {
            // Site name comes first: "Site Name - Page Title"
            return "{$siteName} {$separator} {$title}";
        }

        // Default to append: "Page Title - Site Name"
        return "{$title} {$separator} {$siteName}";
    }

    /**
     * Get the meta description for a post/page
     */
    public function getDescription(?Post $post = null): ?string
    {
        return $post?->getMeta('seo_description')
            ?? $post?->getMeta('post_teaser')
            ?? $this->settings->default_meta_description;
    }

    /**
     * Get the canonical URL for a post/page
     */
    public function getCanonicalUrl(?Post $post = null): string
    {
        if ($post && $customCanonical = $post->getMeta('seo_canonical_url')) {
            return $customCanonical;
        }

        $url = $post?->url ?? url()->current();

        return str($url)
            ->remove('index.php/')
            ->replace('//', '/')
            ->replace(':/', '://')
            ->toString();
    }

    /**
     * Get the robots meta content for a post/page
     */
    public function getRobotsContent(?Post $post = null): string
    {
        $index = $post?->getMeta('seo_robots_index') ?: $this->settings->default_robots_index;
        $follow = $post?->getMeta('seo_robots_follow') ?: $this->settings->default_robots_follow;

        return "{$index}, {$follow}";
    }

    /**
     * Get the OpenGraph title
     */
    public function getOgTitle(?Post $post = null): string
    {
        return $post?->getMeta('seo_og_title')
            ?? $post?->getMeta('seo_title')
            ?? $post?->post_title
            ?? $this->settings->site_name;
    }

    /**
     * Get the OpenGraph description
     */
    public function getOgDescription(?Post $post = null): ?string
    {
        return $post?->getMeta('seo_og_description')
            ?? $this->getDescription($post);
    }

    /**
     * Get the OpenGraph image URL
     */
    public function getOgImage(?Post $post = null): ?string
    {
        // Use the clean accessor method
        if ($post) {
            $media = $post->seoOgImage();
            if ($media) {
                return $media->getFullUrl('og');
            }

            // Fallback to featured image if no SEO image
            if ($post->hasMedia('featured')) {
                return $post->getFirstMedia('featured')?->getFullUrl();
            }
        }

        // No post provided, check for default OG image
        $seoMedia = SiteSettingsMedia::getInstance();
        if ($seoMedia->hasMedia('og-default')) {
            return $seoMedia->getFirstMedia('og-default')?->getFullUrl('og');
        }

        return null;
    }

    /**
     * Get the Twitter title
     * Since we consolidated social media fields, Twitter uses the same title as OG
     */
    public function getTwitterTitle(?Post $post = null): string
    {
        return $this->getOgTitle($post);
    }

    /**
     * Get the Twitter description
     * Since we consolidated social media fields, Twitter uses the same description as OG
     */
    public function getTwitterDescription(?Post $post = null): ?string
    {
        return $this->getOgDescription($post);
    }

    /**
     * Get the Twitter image URL
     */
    public function getTwitterImage(?Post $post = null): ?string
    {
        $seoSettings = app(\FrankenCms\Settings\SeoSettings::class);

        // Use the clean accessor method
        if ($post) {
            $media = $post->seoTwitterImage();
            if ($media) {
                // Determine which conversion to use based on the collection
                $conversionName = $media->collection_name === 'seo-twitter' || $media->collection_name === 'twitter-default'
                    ? 'twitter-summary'
                    : 'twitter';

                return $media->getFullUrl($conversionName);
            }

            // Fallback to featured image if no SEO image
            if ($post->hasMedia('featured')) {
                return $post->getFirstMedia('featured')?->getFullUrl();
            }
        }

        // No post provided, check for default images based on settings
        $seoMedia = SiteSettingsMedia::getInstance();

        if ($seoSettings->use_twitter_summary_card && $seoMedia->hasMedia('twitter-default')) {
            return $seoMedia->getFirstMedia('twitter-default')?->getFullUrl('twitter-summary');
        }

        if ($seoMedia->hasMedia('og-default')) {
            return $seoMedia->getFirstMedia('og-default')?->getFullUrl('twitter');
        }

        return null;
    }

    /**
     * Get the OpenGraph type
     */
    public function getOgType(?Post $post = null): string
    {
        if ($post && $post->post_type === 'post') {
            return 'article';
        }

        return $this->settings->og_type ?? 'website';
    }

    /**
     * Get the theme color
     */
    public function getThemeColor(): ?string
    {
        $color = $this->settings->theme_color;

        if (! $color) {
            return null;
        }

        // Ensure it starts with #
        return str_starts_with($color, '#') ? $color : "#{$color}";
    }
}
