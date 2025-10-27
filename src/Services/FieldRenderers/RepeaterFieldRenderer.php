<?php

namespace FrankenCms\Services\FieldRenderers;

use FrankenCms\Contracts\FieldRendererInterface;
use Illuminate\Support\Collection;

class RepeaterFieldRenderer implements FieldRendererInterface
{
    public function render(mixed $value, ?string $fieldName = null): Collection
    {
        return collect($value ?? []);
    }
}
