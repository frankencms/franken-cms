<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Media Settings Group (franken_cms_media)

        // Featured Image (Single Post View)
        $this->migrator->add('franken_cms_media.featured_aspect_ratio', '16:9');
        $this->migrator->add('franken_cms_media.featured_width', 1200);
        $this->migrator->add('franken_cms_media.featured_custom_width', null);
        $this->migrator->add('franken_cms_media.featured_custom_height', null);
        $this->migrator->add('franken_cms_media.featured_crop', true);

        // Listing Image (Blog Index/Archive Pages)
        $this->migrator->add('franken_cms_media.listing_aspect_ratio', '3:2');
        $this->migrator->add('franken_cms_media.listing_width', 800);
        $this->migrator->add('franken_cms_media.listing_custom_width', null);
        $this->migrator->add('franken_cms_media.listing_custom_height', null);
        $this->migrator->add('franken_cms_media.listing_crop', true);

        // Responsive Images
        $this->migrator->add('franken_cms_media.enable_responsive_images', true);
    }
};
