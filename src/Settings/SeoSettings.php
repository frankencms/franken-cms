<?php

declare(strict_types=1);

namespace FrankenCms\Settings;

use Spatie\LaravelSettings\Settings;

class SeoSettings extends Settings
{
    // General SEO
    public string $site_name;

    public ?string $site_tagline;

    // Title Configuration
    public bool $append_site_name;

    public string $site_name_position; // 'append' or 'prepend'

    public string $title_separator;

    public ?string $default_meta_description;

    // Canonical & Robots
    public bool $enable_canonical;

    public string $default_robots_index;

    public string $default_robots_follow;

    // OpenGraph
    public string $og_type;

    public ?string $fb_app_id;

    // Twitter
    public string $twitter_card_type;

    public ?string $twitter_username;

    // Theme & Appearance
    public ?string $theme_color;

    public static function group(): string
    {
        return 'franken-cms-seo';
    }
}
