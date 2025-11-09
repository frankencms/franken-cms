<?php

namespace FrankenCms\Directives\Providers;

use FrankenCms\Contracts\DirectiveProviderInterface;
use FrankenCms\Contracts\FieldTypeInterface;
use FrankenCms\Services\DirectiveRenderer;
use Illuminate\Support\Facades\Blade;

abstract class BaseDirectiveProvider implements DirectiveProviderInterface
{
    protected DirectiveRenderer $renderer;

    public function __construct()
    {
        $this->renderer = app(DirectiveRenderer::class);
    }

    /**
     * Register the Blade directives for this field type
     */
    public function register(): void
    {
        $fieldType = $this->getFieldType();

        if ($fieldType->isBlockDirective()) {
            $this->registerBlockDirectives($fieldType);
        } else {
            $this->registerInlineDirectives($fieldType);
        }
    }

    /**
     * Register inline (self-closing) directives
     */
    protected function registerInlineDirectives(FieldTypeInterface $fieldType): void
    {
        foreach ($fieldType->getDirectiveNames() as $directiveName) {
            Blade::directive("franken{$directiveName}", function ($expression) use ($fieldType) {
                $code = $this->renderer->renderInlineDirective($fieldType->getName());
                $code = str_replace('{$fieldType}', $fieldType->getName(), $code);
                return str_replace('$expression', $expression, $code);
            });
        }
    }

    /**
     * Register block (opening and closing) directives
     */
    protected function registerBlockDirectives(FieldTypeInterface $fieldType): void
    {
        foreach ($fieldType->getDirectiveNames() as $directiveName) {
            Blade::directive("franken{$directiveName}", function ($expression) use ($fieldType) {
                $code = $this->renderer->renderBlockDirectiveOpen($fieldType->getName());
                $code = str_replace('{$fieldType}', $fieldType->getName(), $code);
                return str_replace('$expression', $expression, $code);
            });

            Blade::directive("endfranken{$directiveName}", function () {
                return $this->renderer->renderBlockDirectiveClose();
            });
        }
    }

    /**
     * Get the field type definition for this provider
     */
    abstract public function getFieldType(): FieldTypeInterface;
}
