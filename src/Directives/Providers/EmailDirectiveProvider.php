<?php

namespace FrankenCms\Directives\Providers;

use Filament\Forms\Components\TextInput;
use FrankenCms\Contracts\FieldTypeInterface;
use FrankenCms\Services\FieldRenderers\TextFieldRenderer;
use FrankenCms\ValueObjects\FieldType;

class EmailDirectiveProvider extends BaseDirectiveProvider
{
    public function getFieldType(): FieldTypeInterface
    {
        return FieldType::make(
            name: 'email',
            directiveNames: ['Email'],
            filamentComponentClass: TextInput::class,
            rendererClass: TextFieldRenderer::class,
            supportsOptions: true,
            isBlockDirective: false
        );
    }
}
