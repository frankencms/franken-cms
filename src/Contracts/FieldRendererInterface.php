<?php

namespace FrankenCms\Contracts;

interface FieldRendererInterface
{
    /**
     * Render the field value for display in templates
     *
     * @param  mixed  $value  The stored field value
     * @param  string|null  $fieldName  The field name (for placeholder generation)
     * @return mixed The rendered value (string, Collection, HtmlString, etc.)
     */
    public function render(mixed $value, ?string $fieldName = null): mixed;
}
