<?php

namespace FrankenCms\Directives\Providers;

use Filament\Forms\Components\Textarea;
use FrankenCms\Contracts\FieldTypeInterface;
use FrankenCms\Services\FieldRenderers\TextFieldRenderer;
use FrankenCms\ValueObjects\FieldType;

class TextareaDirectiveProvider extends BaseDirectiveProvider
{
    public function getFieldType(): FieldTypeInterface
    {
        return FieldType::make(
            name: 'textarea',
            directiveNames: ['Textarea'],
            filamentComponentClass: Textarea::class,
            rendererClass: TextFieldRenderer::class,
            supportsOptions: true,
            isBlockDirective: false
        );
    }
}
