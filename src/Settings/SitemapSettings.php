<?php

declare(strict_types=1);

namespace FrankenCms\Settings;

use Spatie\LaravelSettings\Settings;

class SitemapSettings extends Settings
{
    /**
     * Enable or disable sitemap generation
     */
    public bool $enabled;

    /**
     * Default change frequency for posts
     * Options: always, hourly, daily, weekly, monthly, yearly, never
     */
    public string $default_change_frequency;

    /**
     * Default priority for posts (0.0 to 1.0)
     */
    public float $default_priority;

    /**
     * Maximum URLs per sitemap file (for sitemap index)
     */
    public int $max_urls_per_sitemap;

    /**
     * Exclude specific URLs from sitemap (array of paths)
     */
    public array $excluded_paths;

    /**
     * Include images in sitemap
     */
    public bool $include_images;

    /**
     * Custom sitemap URLs to include in sitemap index
     * Example: ['https://example.com/custom-sitemap.xml', '/news-sitemap.xml']
     */
    public array $custom_sitemaps;

    public static function group(): string
    {
        return 'franken-cms-sitemap';
    }
}
