<?php

namespace FrankenCms\Services\FieldRenderers;

use FrankenCms\Contracts\FieldRendererInterface;
use Illuminate\Support\HtmlString;

class RichEditorFieldRenderer implements FieldRendererInterface
{
    public function render(mixed $value): HtmlString
    {
        return new HtmlString($value ?? '');
    }
}
