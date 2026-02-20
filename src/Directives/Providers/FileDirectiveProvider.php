<?php

namespace FrankenCms\Directives\Providers;

use Filament\Forms\Components\FileUpload;
use FrankenCms\Contracts\FieldTypeInterface;
use FrankenCms\Services\FieldRenderers\FileFieldRenderer;
use FrankenCms\ValueObjects\FieldType;

class FileDirectiveProvider extends BaseDirectiveProvider
{
    public function getFieldType(): FieldTypeInterface
    {
        return FieldType::make(
            name: 'file',
            directiveNames: ['File'],
            filamentComponentClass: FileUpload::class,
            rendererClass: FileFieldRenderer::class,
            supportsOptions: true,
            isBlockDirective: false
        );
    }
}
