<?php

use FrankenCms\Enums\PermalinkStructure;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Permalink Settings Group (franken_cms_permalinks)
        $this->migrator->add('franken_cms_permalinks.permalink_structure', PermalinkStructure::POST_NAME->value);
        $this->migrator->add('franken_cms_permalinks.custom_permalink_structure', []);
        $this->migrator->add('franken_cms_permalinks.category_base_url', 'category');
        $this->migrator->add('franken_cms_permalinks.tag_base_url', 'tag');
    }
};
