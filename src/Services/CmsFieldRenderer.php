<?php

namespace FrankenCms\Services;

use FrankenCms\Contracts\FieldRendererInterface;
use FrankenCms\Services\FieldRenderers\BooleanFieldRenderer;
use FrankenCms\Services\FieldRenderers\FileFieldRenderer;
use FrankenCms\Services\FieldRenderers\RepeaterFieldRenderer;
use FrankenCms\Services\FieldRenderers\RichEditorFieldRenderer;
use FrankenCms\Services\FieldRenderers\SelectFieldRenderer;
use FrankenCms\Services\FieldRenderers\SpatieImageFieldRenderer;
use FrankenCms\Services\FieldRenderers\TextFieldRenderer;
use InvalidArgumentException;

class CmsFieldRenderer
{
    /**
     * Map of field types to their renderer classes
     */
    protected array $renderers = [
        'text'       => TextFieldRenderer::class,
        'textarea'   => TextFieldRenderer::class,
        'email'      => TextFieldRenderer::class,
        'url'        => TextFieldRenderer::class,
        'number'     => TextFieldRenderer::class,
        'select'     => SelectFieldRenderer::class,
        'file'       => FileFieldRenderer::class,
        'image'      => SpatieImageFieldRenderer::class,
        'repeater'   => RepeaterFieldRenderer::class,
        'richEditor' => RichEditorFieldRenderer::class,
        'toggle'     => BooleanFieldRenderer::class,
        'checkbox'   => BooleanFieldRenderer::class,
        'tags'       => TextFieldRenderer::class,
    ];

    /**
     * Render a field value based on its type
     */
    public function render(string $fieldType, mixed $value, ?string $fieldName = null): mixed
    {
        $rendererClass = $this->renderers[$fieldType] ?? TextFieldRenderer::class;

        $renderer = app($rendererClass);

        return $renderer->render($value, $fieldName);
    }

    /**
     * Register a custom field renderer
     */
    public function registerRenderer(string $fieldType, string $rendererClass): void
    {
        if (! is_subclass_of($rendererClass, FieldRendererInterface::class)) {
            throw new InvalidArgumentException(
                'Renderer class must implement FieldRendererInterface'
            );
        }

        $this->renderers[$fieldType] = $rendererClass;
    }

    /**
     * Get all registered renderers
     */
    public function getRenderers(): array
    {
        return $this->renderers;
    }
}
