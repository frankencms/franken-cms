<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Sitemap Settings Group (franken_cms_sitemap)
        $this->migrator->add('franken_cms_sitemap.enabled', true);

        // Default change frequency: weekly
        $this->migrator->add('franken_cms_sitemap.default_change_frequency', 'weekly');

        // Default priority: 0.5
        $this->migrator->add('franken_cms_sitemap.default_priority', 0.5);

        // Max 50,000 URLs per sitemap (Google's limit)
        $this->migrator->add('franken_cms_sitemap.max_urls_per_sitemap', 50000);

        // No excluded paths by default
        $this->migrator->add('franken_cms_sitemap.excluded_paths', []);

        // Include images by default
        $this->migrator->add('franken_cms_sitemap.include_images', true);
    }
};
