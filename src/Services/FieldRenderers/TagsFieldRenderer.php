<?php

namespace FrankenCms\Services\FieldRenderers;

use FrankenCms\Contracts\FieldRendererInterface;

class TagsFieldRenderer implements FieldRendererInterface
{
    /**
     * Render tags field data
     *
     * Returns the array of tags as-is for use in templates
     */
    public function render(mixed $value, ?string $fieldName = null): array
    {
        // Tags are stored as an array, return as-is
        return is_array($value) ? $value : ($value ? [$value] : []);
    }
}
