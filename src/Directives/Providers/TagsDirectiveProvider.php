<?php

namespace FrankenCms\Directives\Providers;

use Filament\Forms\Components\TagsInput;
use FrankenCms\Contracts\FieldTypeInterface;
use FrankenCms\Services\FieldRenderers\TagsFieldRenderer;
use FrankenCms\ValueObjects\FieldType;
use Illuminate\Support\Facades\Blade;

class TagsDirectiveProvider extends BaseDirectiveProvider
{
    /**
     * Register the tags block directives with custom parsing
     */
    public function register(): void
    {
        Blade::directive('frankenTags', function ($expression) {
            // Wrap expression in array to handle multiple arguments
            $code = "<?php \$__tagsExpression = [{$expression}]; ?>";
            $code .= $this->renderer->renderTagsDirectiveOpen();
            return $code;
        });

        Blade::directive('endFrankenTags', function () {
            return $this->renderer->renderTagsDirectiveClose();
        });
    }

    public function getFieldType(): FieldTypeInterface
    {
        return FieldType::make(
            name: 'tags',
            directiveNames: ['Tags'],
            filamentComponentClass: TagsInput::class,
            rendererClass: TagsFieldRenderer::class,
            supportsOptions: true,
            isBlockDirective: true
        );
    }
}
