<?php

namespace FrankenCms\Settings;

use FrankenCms\Enums\PermalinkStructure;
use Spatie\LaravelSettings\Settings;

class PermalinkSettings extends Settings
{
    public string $permalink_structure = PermalinkStructure::POST_NAME->value;
    /**
     * The custom permalink structure will be an array of selected tags.
     * For example: ['%year%', '%month%', '%postname%']
     */
    public array $custom_permalink_structure = [];
    public string $category_base_url = 'category';
    public string $tag_base_url = 'tag';

    public static function group(): string
    {
        return 'franken_cms_permalinks';
    }
}
