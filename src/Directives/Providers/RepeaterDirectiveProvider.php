<?php

namespace FrankenCms\Directives\Providers;

use Filament\Forms\Components\Repeater;
use FrankenCms\Contracts\FieldTypeInterface;
use FrankenCms\Services\FieldRenderers\RepeaterFieldRenderer;
use FrankenCms\ValueObjects\FieldType;
use Illuminate\Support\Facades\Blade;

class RepeaterDirectiveProvider extends BaseDirectiveProvider
{
    /**
     * Register the repeater block directives with custom parsing
     */
    public function register(): void
    {
        Blade::directive('frankenRepeater', function ($expression) {
            // Wrap expression in array to handle multiple arguments
            $code = "<?php \$__repeaterExpression = [{$expression}]; ?>";
            $code .= $this->renderer->renderRepeaterDirectiveOpen();
            return $code;
        });

        Blade::directive('endFrankenRepeater', function () {
            return $this->renderer->renderRepeaterDirectiveClose();
        });
    }

    public function getFieldType(): FieldTypeInterface
    {
        return FieldType::make(
            name: 'repeater',
            directiveNames: ['Repeater'],
            filamentComponentClass: Repeater::class,
            rendererClass: RepeaterFieldRenderer::class,
            supportsOptions: true,
            isBlockDirective: true
        );
    }
}
