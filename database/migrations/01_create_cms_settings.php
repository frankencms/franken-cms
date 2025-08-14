<?php

use FrankenCMS\FrankenCms\Enums\DateFormat;
use FrankenCMS\FrankenCms\Enums\PermalinkStructure;
use FrankenCMS\FrankenCms\Enums\TimeFormat;
use FrankenCMS\FrankenCms\Enums\UserRole;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        /*
        * General Settings
        */
        $this->migrator->add('cms.title', 'Franken CMS');
        $this->migrator->add('cms.tagline');
        $this->migrator->add('cms.icon');
        $this->migrator->add('cms.membership', false);
        $this->migrator->add('cms.new_user_default_role', UserRole::SUBSCRIBER->value);
        $this->migrator->add('cms.language');
        $this->migrator->add('cms.timezone', 'UTC+0');
        $this->migrator->add('cms.date_format', DateFormat::FULL_MONTH_DAY_YEAR->value);
        $this->migrator->add('cms.custom_date_format');
        $this->migrator->add('cms.time_format', TimeFormat::HOURS_12_MINUTES_LOWERCASE->value);
        $this->migrator->add('cms.custom_time_format');
        $this->migrator->add('cms.week_starts_on', 'Monday');

        /*
         * Reading Settings
         */
        $this->migrator->add('cms.homepage_displays', 'latest_posts');
        $this->migrator->add('cms.home_page');
        $this->migrator->add('cms.post_page');
        $this->migrator->add('cms.posts_per_page', 10);
        $this->migrator->add('cms.syndicate_feeds', 10);
        $this->migrator->add('cms.include_in_feed', 'full_text');
        $this->migrator->add('cms.discourage_search_visibility', false);

        /*
         * Media Settings
         */
        $this->migrator->add('cms.thumbnail_width', 150);
        $this->migrator->add('cms.thumbnail_height', 150);
        $this->migrator->add('cms.thumbnail_crop', false);
        $this->migrator->add('cms.medium_width', 300);
        $this->migrator->add('cms.medium_height', 300);
        $this->migrator->add('cms.large_width', 1024);
        $this->migrator->add('cms.large_height', 1024);

        /*
         * Permalink Settings
         */
        $this->migrator->add('cms.permalink_structure', PermalinkStructure::POST_NAME->value);
        $this->migrator->add('cms.custom_permalink_structure', []);
        $this->migrator->add('cms.category_base_url', 'category');
        $this->migrator->add('cms.tag_base_url', 'tag');

    }
};
