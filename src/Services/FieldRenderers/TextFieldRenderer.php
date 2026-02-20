<?php

namespace FrankenCms\Services\FieldRenderers;

use FrankenCms\Contracts\FieldRendererInterface;
use FrankenCms\Helpers\TemplateHelpers;

class TextFieldRenderer implements FieldRendererInterface
{
    public function render(mixed $value, ?string $fieldName = null): string
    {
        // If we have a value, return it
        if ($value) {
            return e($value);
        }

        // If no value and we have a field name, return placeholder text
        if ($fieldName) {
            return TemplateHelpers::wrapTextPlaceholder($fieldName);
        }

        // Otherwise return empty string
        return '';
    }
}
