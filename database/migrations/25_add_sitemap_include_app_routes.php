<?php

use Illuminate\Support\Facades\Cache;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Auto-generate sitemap-routes.xml for host app routes by default
        $this->migrator->add('franken_cms_sitemap.include_app_routes', true);

        // The sitemap index is cached forever under 'sitemap_index', and it's
        // only ever invalidated by the SettingsSaved event. $migrator->add()
        // writes settings directly without firing that event, so upgraded
        // apps would keep serving a pre-upgrade index that never lists the
        // new sitemap-routes.xml. Force the cache clear here instead.
        Cache::forget('sitemap_index');
    }
};
