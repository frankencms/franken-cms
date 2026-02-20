<?php

namespace FrankenCms\Directives\Providers;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use FrankenCms\Contracts\FieldTypeInterface;
use FrankenCms\Services\FieldRenderers\SpatieImageFieldRenderer;
use FrankenCms\ValueObjects\FieldType;

class ImageDirectiveProvider extends BaseDirectiveProvider
{
    public function getFieldType(): FieldTypeInterface
    {
        return FieldType::make(
            name: 'image',
            directiveNames: ['Image', 'MediaImage'],
            filamentComponentClass: SpatieMediaLibraryFileUpload::class,
            rendererClass: SpatieImageFieldRenderer::class,
            supportsOptions: true,
            isBlockDirective: false
        );
    }
}
