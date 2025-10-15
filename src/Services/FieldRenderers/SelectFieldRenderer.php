<?php

namespace FrankenCms\Services\FieldRenderers;

use FrankenCms\Contracts\FieldRendererInterface;

class SelectFieldRenderer implements FieldRendererInterface
{
    public function render(mixed $value): string
    {
        return e($value ?? '');
    }
}
