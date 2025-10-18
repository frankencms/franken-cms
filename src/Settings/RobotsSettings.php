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
     * Block all search engines from indexing the entire site
     * When enabled, adds "User-agent: *\nDisallow: /" as the first rule
     * Useful during development to prevent indexing of work-in-progress content
     */
    public bool $discourage_indexing;

    /**
     * User agent rules
     * Structure: [
     *   ['user_agent' => '*', 'rules' => ['Disallow: /admin', 'Allow: /'], 'crawl_delay' => null],
     *   ['user_agent' => 'Googlebot', 'rules' => ['Allow: /'], 'crawl_delay' => 1],
     * ]
     */
    public array $user_agents;

    public static function group(): string
    {
        return 'franken-cms-robots';
    }
}
