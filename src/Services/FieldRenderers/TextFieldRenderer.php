<?php

namespace FrankenCms\Services\FieldRenderers;

use FrankenCms\Contracts\FieldRendererInterface;
use Illuminate\Support\HtmlString;

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
            $fieldLabel = str($fieldName)->replace(['.', '_'], ' ')->title();
            return '[' . $fieldLabel . ']';
        }

        // Otherwise return empty string
        return '';
    }
}
