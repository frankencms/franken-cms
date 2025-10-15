<?php

namespace FrankenCms\Services\FieldRenderers;

use FrankenCms\Contracts\FieldRendererInterface;

class BooleanFieldRenderer implements FieldRendererInterface
{
    public function render(mixed $value): bool
    {
        return (bool) $value;
    }
}
