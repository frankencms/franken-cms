<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Reading Settings Group (franken_cms_reading)
        $this->migrator->add('franken_cms_reading.home_page');
        $this->migrator->add('franken_cms_reading.post_page');
        $this->migrator->add('franken_cms_reading.posts_per_page', 10);
        $this->migrator->add('franken_cms_reading.enable_feeds', true);
        $this->migrator->add('franken_cms_reading.syndicate_feeds', 10);
        $this->migrator->add('franken_cms_reading.include_in_feed', 'full_text');
        $this->migrator->add('franken_cms_reading.discourage_search_visibility', false);
    }
};
