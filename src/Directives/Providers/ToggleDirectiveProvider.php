<?php

namespace FrankenCms\Directives\Providers;

use Filament\Forms\Components\Toggle;
use FrankenCms\Contracts\FieldTypeInterface;
use FrankenCms\Services\FieldRenderers\BooleanFieldRenderer;
use FrankenCms\ValueObjects\FieldType;

class ToggleDirectiveProvider extends BaseDirectiveProvider
{
    public function getFieldType(): FieldTypeInterface
    {
        return FieldType::make(
            name: 'toggle',
            directiveNames: ['Toggle'],
            filamentComponentClass: Toggle::class,
            rendererClass: BooleanFieldRenderer::class,
            supportsOptions: true,
            isBlockDirective: false
        );
    }
}
