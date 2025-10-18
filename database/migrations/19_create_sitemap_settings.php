<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Sitemap Settings Group (franken-cms-sitemap)
        $this->migrator->add('franken-cms-sitemap.enabled', true);

        // Include both posts and pages by default
        $this->migrator->add('franken-cms-sitemap.included_post_types', ['post', 'page']);

        // Default change frequency: weekly
        $this->migrator->add('franken-cms-sitemap.default_change_frequency', 'weekly');

        // Default priority: 0.5
        $this->migrator->add('franken-cms-sitemap.default_priority', 0.5);

        // Max 50,000 URLs per sitemap (Google's limit)
        $this->migrator->add('franken-cms-sitemap.max_urls_per_sitemap', 50000);

        // No excluded paths by default
        $this->migrator->add('franken-cms-sitemap.excluded_paths', []);

        // Include images by default
        $this->migrator->add('franken-cms-sitemap.include_images', true);
    }
};
