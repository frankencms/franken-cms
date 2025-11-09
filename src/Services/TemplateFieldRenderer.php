<?php

namespace FrankenCms\Services;

use FrankenCms\Contracts\FieldRendererInterface;
use FrankenCms\Registries\FieldTypeRegistry;
use FrankenCms\Services\FieldRenderers\TextFieldRenderer;
use InvalidArgumentException;

class TemplateFieldRenderer
{
    /**
     * Custom renderer overrides
     */
    protected array $customRenderers = [];

    public function __construct(
        protected FieldTypeRegistry $fieldTypeRegistry
    ) {}

    /**
     * Render a field value based on its type
     */
    public function render(string $fieldType, mixed $value, ?string $fieldName = null): mixed
    {
        // Check for custom renderer override first
        if (isset($this->customRenderers[$fieldType])) {
            $rendererClass = $this->customRenderers[$fieldType];
        } else {
            // Get renderer from registry
            $fieldTypeDefinition = $this->fieldTypeRegistry->get($fieldType);
            $rendererClass = $fieldTypeDefinition?->getRendererClass() ?? TextFieldRenderer::class;
        }

        $renderer = app($rendererClass);

        return $renderer->render($value, $fieldName);
    }

    /**
     * Register a custom field renderer override
     */
    public function registerRenderer(string $fieldType, string $rendererClass): void
    {
        if (! is_subclass_of($rendererClass, FieldRendererInterface::class)) {
            throw new InvalidArgumentException(
                'Renderer class must implement FieldRendererInterface'
            );
        }

        $this->customRenderers[$fieldType] = $rendererClass;
    }

    /**
     * Get all registered renderers (from registry and custom overrides)
     */
    public function getRenderers(): array
    {
        $renderers = [];

        // Get renderers from registry
        foreach ($this->fieldTypeRegistry->all() as $fieldType) {
            $renderers[$fieldType->getName()] = $fieldType->getRendererClass();
        }

        // Merge custom overrides
        return array_merge($renderers, $this->customRenderers);
    }
}
