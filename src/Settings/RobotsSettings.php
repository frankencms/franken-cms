<?php

declare(strict_types=1);

namespace FrankenCms\Settings;

use Spatie\LaravelSettings\Settings;

class RobotsSettings extends Settings
{
    /**
     * Enable or disable dynamic robots.txt generation
     */
    public bool $enabled;

    /**
     * User agent rules
     * Structure: [
     *   ['user_agent' => '*', 'rules' => ['Disallow: /admin', 'Allow: /'], 'crawl_delay' => null],
     *   ['user_agent' => 'Googlebot', 'rules' => ['Allow: /'], 'crawl_delay' => 1],
     * ]
     */
    public array $user_agents;

    /**
     * Additional sitemap URLs to include in robots.txt
     * Auto-generated sitemaps will be added automatically
     */
    public array $additional_sitemaps;

    /**
     * Canonical host directive (optional)
     * Example: https://example.com
     */
    public ?string $host;

    public static function group(): string
    {
        return 'franken-cms-robots';
    }
}
