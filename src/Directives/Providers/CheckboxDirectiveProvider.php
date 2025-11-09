<?php

namespace FrankenCms\Directives\Providers;

use Filament\Forms\Components\Checkbox;
use FrankenCms\Contracts\FieldTypeInterface;
use FrankenCms\Services\FieldRenderers\BooleanFieldRenderer;
use FrankenCms\ValueObjects\FieldType;

class CheckboxDirectiveProvider extends BaseDirectiveProvider
{
    public function getFieldType(): FieldTypeInterface
    {
        return FieldType::make(
            name: 'checkbox',
            directiveNames: ['Checkbox'],
            filamentComponentClass: Checkbox::class,
            rendererClass: BooleanFieldRenderer::class,
            supportsOptions: true,
            isBlockDirective: false
        );
    }
}
