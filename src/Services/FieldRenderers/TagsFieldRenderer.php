<?php

namespace FrankenCms\Services\FieldRenderers;

use FrankenCms\Contracts\FieldRendererInterface;
use Illuminate\Support\Collection;

class TagsFieldRenderer implements FieldRendererInterface
{
    /**
     * Render tags field data
     *
     * Returns a collection of tags for use in templates with collection methods
     */
    public function render(mixed $value, ?string $fieldName = null): Collection
    {
        // Tags are stored as an array, return as a collection
        $tags = is_array($value) ? $value : ($value ? [$value] : []);

        return collect($tags);
    }
}
