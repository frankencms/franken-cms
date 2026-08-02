<?php

declare(strict_types=1);

namespace FrankenCms\Services;

use FrankenCms\Models\Post;
use FrankenCms\Models\SiteSettingsMedia;
use FrankenCms\Settings\ReadingSettings;
use FrankenCms\Settings\SeoSettings;

class SeoService
{
    public function __construct(
        protected SeoSettings $settings
    ) {}

    /**
     * Get the raw SEO title for a post/page (site-name affixes are applied
     * by the laravel/head defaults layer, not here).
     */
    public function getTitle(?Post $post = null): string
    {
        return $post?->getMeta('seo_title') ?? $post?->post_title ?? $this->settings->site_name;
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

        // Check if this is the home page - always use root URL
        if ($post) {
            $readingSettings = app(ReadingSettings::class);
            if ($readingSettings->home_page && $post->post_slug === $readingSettings->home_page) {
                return url('/');
            }
        }

        // Get the relative URL from post or current request
        $relativeUrl = $post?->url ?? request()->path();

        // Convert to absolute URL using url() helper
        $absoluteUrl = url($relativeUrl);

        $url = str($absoluteUrl)
            ->remove('index.php/')
            ->toString();

        // Add trailing slash for blog listing page and pages with children
        if ($post) {
            $readingSettings = app(ReadingSettings::class);
            $shouldAddTrailingSlash = false;

            // Check if this is the blog listing page
            if ($readingSettings->post_page && $post->post_slug === $readingSettings->post_page) {
                $shouldAddTrailingSlash = true;
            }

            // Check if this page has children
            if ($post->post_type === 'page' && $post->children()->exists()) {
                $shouldAddTrailingSlash = true;
            }

            // Add trailing slash if needed and not already present
            if ($shouldAddTrailingSlash && ! str_ends_with($url, '/')) {
                $url .= '/';
            }
        }

        return $url;
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
     * Get the Twitter image URL
     */
    public function getTwitterImage(?Post $post = null): ?string
    {
        $seoSettings = app(SeoSettings::class);

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
