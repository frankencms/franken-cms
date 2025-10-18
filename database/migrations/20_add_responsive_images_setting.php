<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('cms_media.enable_responsive_images', true);
    }

    public function down(): void
    {
        $this->migrator->delete('cms_media.enable_responsive_images');
    }
};
