<?php

namespace FrankenCms\Settings;

use FrankenCms\Enums\DateFormat;
use FrankenCms\Enums\PermalinkStructure;
use FrankenCms\Enums\TimeFormat;
use FrankenCms\Enums\UserRole;
use Spatie\LaravelSettings\Settings;

class CmsSettings extends Settings
{
    public string $title;
    public ?string $tagline = null;
    public ?string $icon = null;
    public bool $membership = false;
    public ?string $new_user_default_role = UserRole::SUBSCRIBER->value;
    public ?string $language = null;
    public ?string $timezone = 'UTC+0';
    public ?string $date_format = DateFormat::FULL_MONTH_DAY_YEAR->value;
    public ?string $custom_date_format = null;
    public ?string $time_format = TimeFormat::HOURS_12_MINUTES_LOWERCASE->value;
    public ?string $custom_time_format = null;

    // Reading Settings (Reading Tab)
    public string $homepage_displays = 'latest_posts'; // radio: 'latest_posts' or 'static_page'
    public ?string $home_page = null;    // when homepage_displays is "static_page"
    public ?string $post_page = null;    // when postpage_displays is "static_page"
    public ?int $posts_per_page = 10;        // "Blog Pages Show At Most" posts
    public ?int $syndicate_feeds = 10;   // "Syndicate Feeds Show The Most Recent" items
    // These are already present (and used in both General and Reading contexts):
    public ?string $include_in_feed = 'full_text';
    public ?string $discourage_search_visibility = null;

    // Media Settings (Media Tab)
    // Thumbnail settings
    public int $thumbnail_width = 150;
    public int $thumbnail_height = 150;
    public bool $thumbnail_crop = false;

    // Medium size settings
    public int $medium_width = 300;
    public int $medium_height = 300;

    // Large size settings
    public int $large_width = 1024;
    public int $large_height = 1024;

    // Permalink Settings (Permalinks Tab)
    public string $permalink_structure = PermalinkStructure::POST_NAME->value;
    /**
     * The custom permalink structure will be an array of selected tags.
     * For example: ['%year%', '%month%', '%postname%']
     */
    public array $custom_permalink_structure = [];
    public string $category_base_url = 'category';
    public string $tag_base_url = 'tag';

    public static function group(): string
    {
        return 'cms';
    }
}
