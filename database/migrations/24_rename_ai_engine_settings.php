<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->rename('franken_cms_ai.provider', 'franken_cms_ai.text_provider');
        $this->migrator->rename('franken_cms_ai.model', 'franken_cms_ai.text_model');
        $this->migrator->rename('franken_cms_ai.featured_image_provider', 'franken_cms_ai.image_provider');
        $this->migrator->rename('franken_cms_ai.featured_image_model', 'franken_cms_ai.image_model');
        $this->migrator->rename('franken_cms_ai.featured_image_quality', 'franken_cms_ai.image_quality');
    }
};
