<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Auto-generate sitemap-routes.xml for host app routes by default
        $this->migrator->add('franken_cms_sitemap.include_app_routes', true);
    }
};
