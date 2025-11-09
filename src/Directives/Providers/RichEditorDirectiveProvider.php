<?php

namespace FrankenCms\Directives\Providers;

use Filament\Forms\Components\RichEditor;
use FrankenCms\Contracts\FieldTypeInterface;
use FrankenCms\Services\FieldRenderers\RichEditorFieldRenderer;
use FrankenCms\ValueObjects\FieldType;

class RichEditorDirectiveProvider extends BaseDirectiveProvider
{
    public function getFieldType(): FieldTypeInterface
    {
        return FieldType::make(
            name: 'richEditor',
            directiveNames: ['RichEditor'],
            filamentComponentClass: RichEditor::class,
            rendererClass: RichEditorFieldRenderer::class,
            supportsOptions: true,
            isBlockDirective: false
        );
    }
}
