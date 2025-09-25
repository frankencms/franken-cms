<?php

namespace FrankenCms\Settings;

use Spatie\LaravelSettings\Settings;

class ReadingSettings extends Settings
{
    public string $homepage_displays = 'latest_posts'; // radio: 'latest_posts' or 'static_page'
    public ?string $home_page = null;    // when homepage_displays is "static_page"
    public ?string $post_page = null;    // when postpage_displays is "static_page"
    public ?int $posts_per_page = 10;        // "Blog Pages Show At Most" posts
    public ?int $syndicate_feeds = 10;   // "Syndicate Feeds Show The Most Recent" items
    public ?string $include_in_feed = 'full_text';
    public ?string $discourage_search_visibility = null;

    public static function group(): string
    {
        return 'cms_reading';
    }
}
