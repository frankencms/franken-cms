<?php

namespace FrankenCms\Directives\Providers;

use Filament\Forms\Components\Select;
use FrankenCms\Contracts\FieldTypeInterface;
use FrankenCms\Services\FieldRenderers\SelectFieldRenderer;
use FrankenCms\ValueObjects\FieldType;

class SelectDirectiveProvider extends BaseDirectiveProvider
{
    public function getFieldType(): FieldTypeInterface
    {
        return FieldType::make(
            name: 'select',
            directiveNames: ['Select'],
            filamentComponentClass: Select::class,
            rendererClass: SelectFieldRenderer::class,
            supportsOptions: true,
            isBlockDirective: false
        );
    }
}
