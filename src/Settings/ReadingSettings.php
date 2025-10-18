<?php

namespace FrankenCms\Settings;

use Spatie\LaravelSettings\Settings;

class ReadingSettings extends Settings
{
    public ?string $home_page = null;           // The page slug to use as homepage
    public ?string $post_page = null;           // The page slug to use for blog posts listing
    public ?int $posts_per_page = 10;           // "Blog Pages Show At Most" posts
    public bool $enable_feeds = true;           // Enable RSS and Atom feeds
    public ?int $syndicate_feeds = 10;          // "Syndicate Feeds Show The Most Recent" items
    public ?string $include_in_feed = 'full_text';

    public static function group(): string
    {
        return 'cms_reading';
    }
}
