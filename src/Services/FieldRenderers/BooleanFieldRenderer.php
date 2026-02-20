<?php

namespace FrankenCms\Services\FieldRenderers;

use FrankenCms\Contracts\FieldRendererInterface;

class BooleanFieldRenderer implements FieldRendererInterface
{
    public function render(mixed $value, ?string $fieldName = null): bool
    {
        return (bool) $value;
    }
}
