<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Media Settings Group (cms_media)
        $this->migrator->add('cms_media.thumbnail_width', 150);
        $this->migrator->add('cms_media.thumbnail_height', 150);
        $this->migrator->add('cms_media.thumbnail_crop', false);
        $this->migrator->add('cms_media.medium_width', 300);
        $this->migrator->add('cms_media.medium_height', 300);
        $this->migrator->add('cms_media.large_width', 1024);
        $this->migrator->add('cms_media.large_height', 1024);
    }
};