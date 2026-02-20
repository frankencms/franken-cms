<?php

namespace FrankenCms\Directives\Providers;

use Filament\Forms\Components\TextInput;
use FrankenCms\Contracts\FieldTypeInterface;
use FrankenCms\Services\FieldRenderers\TextFieldRenderer;
use FrankenCms\ValueObjects\FieldType;

class UrlDirectiveProvider extends BaseDirectiveProvider
{
    public function getFieldType(): FieldTypeInterface
    {
        return FieldType::make(
            name: 'url',
            directiveNames: ['Url'],
            filamentComponentClass: TextInput::class,
            rendererClass: TextFieldRenderer::class,
            supportsOptions: true,
            isBlockDirective: false
        );
    }
}
