<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Reading Settings Group (cms_reading)
        $this->migrator->add('cms_reading.home_page');
        $this->migrator->add('cms_reading.post_page');
        $this->migrator->add('cms_reading.posts_per_page', 10);
        $this->migrator->add('cms_reading.syndicate_feeds', 10);
        $this->migrator->add('cms_reading.include_in_feed', 'full_text');
        $this->migrator->add('cms_reading.discourage_search_visibility', false);
    }
};