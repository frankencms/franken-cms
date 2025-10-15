<?php

namespace FrankenCms\Services\FieldRenderers;

use FrankenCms\Contracts\FieldRendererInterface;

class TextFieldRenderer implements FieldRendererInterface
{
    public function render(mixed $value): string
    {
        return e($value ?? '');
    }
}
