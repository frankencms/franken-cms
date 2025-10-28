<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // SEO Settings Group (franken-cms-seo)
        $this->migrator->add('franken-cms-seo.site_name', config('app.name', 'Franken CMS'));
        $this->migrator->add('franken-cms-seo.site_tagline', null);

        // Title Configuration
        $this->migrator->add('franken-cms-seo.append_site_name', true);
        $this->migrator->add('franken-cms-seo.site_name_position', 'append');
        $this->migrator->add('franken-cms-seo.title_separator', '-');

        $this->migrator->add('franken-cms-seo.default_meta_description', null);

        // Canonical & Robots
        $this->migrator->add('franken-cms-seo.enable_canonical', true);
        $this->migrator->add('franken-cms-seo.default_robots_index', 'index');
        $this->migrator->add('franken-cms-seo.default_robots_follow', 'follow');

        // OpenGraph
        $this->migrator->add('franken-cms-seo.og_type', 'website');
        $this->migrator->add('franken-cms-seo.fb_app_id', null);

        // Twitter
        $this->migrator->add('franken-cms-seo.use_twitter_summary_card', false);
        $this->migrator->add('franken-cms-seo.twitter_username', null);

        // Theme & Appearance
        $this->migrator->add('franken-cms-seo.theme_color', null);
    }
};
