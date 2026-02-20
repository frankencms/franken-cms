<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // SEO Settings Group (franken_cms_seo)
        $this->migrator->add('franken_cms_seo.site_name', config('app.name', 'Franken CMS'));
        $this->migrator->add('franken_cms_seo.site_tagline', null);

        // Title Configuration
        $this->migrator->add('franken_cms_seo.append_site_name', true);
        $this->migrator->add('franken_cms_seo.site_name_position', 'append');
        $this->migrator->add('franken_cms_seo.title_separator', '-');

        $this->migrator->add('franken_cms_seo.default_meta_description', null);

        // Canonical & Robots
        $this->migrator->add('franken_cms_seo.enable_canonical', true);
        $this->migrator->add('franken_cms_seo.default_robots_index', 'index');
        $this->migrator->add('franken_cms_seo.default_robots_follow', 'follow');

        // OpenGraph
        $this->migrator->add('franken_cms_seo.og_type', 'website');
        $this->migrator->add('franken_cms_seo.fb_app_id', null);

        // Twitter
        $this->migrator->add('franken_cms_seo.use_twitter_summary_card', false);
        $this->migrator->add('franken_cms_seo.twitter_username', null);

        // Theme & Appearance
        $this->migrator->add('franken_cms_seo.theme_color', null);
    }
};
