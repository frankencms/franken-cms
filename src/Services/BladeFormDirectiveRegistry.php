<?php

declare(strict_types=1);

namespace FrankenCms\Services;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;

class BladeFormDirectiveRegistry
{
    protected array $fieldDefinitions = [];
    protected array $layoutDefinitions = [];
    protected array $currentSectionStack = [];

    public function __construct()
    {
        $this->registerFormFields();
        $this->registerLayoutComponents();
    }

    /**
     * Register all form field directives
     */
    protected function registerFormFields(): void
    {
        $formFields = [
            'textInput' => [
                'component' => 'Filament\Forms\Components\TextInput',
                'closing'   => false,
            ],
            'textarea' => [
                'component' => 'Filament\Forms\Components\Textarea',
                'closing'   => false,
            ],
            'select' => [
                'component' => 'Filament\Forms\Components\Select',
                'closing'   => false,
            ],
            'toggle' => [
                'component' => 'Filament\Forms\Components\Toggle',
                'closing'   => false,
            ],
            'checkbox' => [
                'component' => 'Filament\Forms\Components\Checkbox',
                'closing'   => false,
            ],
            'radio' => [
                'component' => 'Filament\Forms\Components\Radio',
                'closing'   => false,
            ],
            'dateTimePicker' => [
                'component' => 'Filament\Forms\Components\DateTimePicker',
                'closing'   => false,
            ],
            'datePicker' => [
                'component' => 'Filament\Forms\Components\DatePicker',
                'closing'   => false,
            ],
            'timePicker' => [
                'component' => 'Filament\Forms\Components\TimePicker',
                'closing'   => false,
            ],
            'fileUpload' => [
                'component' => 'Filament\Forms\Components\FileUpload',
                'closing'   => false,
            ],
            'richEditor' => [
                'component' => 'Filament\Forms\Components\RichEditor',
                'closing'   => false,
            ],
            'markdownEditor' => [
                'component' => 'Filament\Forms\Components\MarkdownEditor',
                'closing'   => false,
            ],
            'keyValue' => [
                'component' => 'Filament\Forms\Components\KeyValue',
                'closing'   => false,
            ],
            'repeater' => [
                'component' => 'Filament\Forms\Components\Repeater',
                'closing'   => true,
            ],
            'colorPicker' => [
                'component' => 'Filament\Forms\Components\ColorPicker',
                'closing'   => false,
            ],
        ];

        foreach ($formFields as $directiveName => $config) {
            $this->registerFormFieldDirective($directiveName, $config);
        }
    }

    /**
     * Register all layout component directives
     */
    protected function registerLayoutComponents(): void
    {
        $layoutComponents = [
            'section' => [
                'component' => 'Filament\Schemas\Components\Section',
                'closing'   => true,
            ],
            'grid' => [
                'component' => 'Filament\Schemas\Components\Grid',
                'closing'   => true,
            ],
            'fieldset' => [
                'component' => 'Filament\Schemas\Components\Fieldset',
                'closing'   => true,
            ],
            'tabs' => [
                'component' => 'Filament\Schemas\Components\Tabs',
                'closing'   => true,
            ],
            'tab' => [
                'component' => 'Filament\Schemas\Components\Tabs\Tab',
                'closing'   => true,
            ],
            'wizard' => [
                'component' => 'Filament\Schemas\Components\Wizard',
                'closing'   => true,
            ],
            'wizardStep' => [
                'component' => 'Filament\Schemas\Components\Wizard\Step',
                'closing'   => true,
            ],
        ];

        foreach ($layoutComponents as $directiveName => $config) {
            $this->registerLayoutComponentDirective($directiveName, $config);
        }
    }

    /**
     * Register a form field directive
     */
    protected function registerFormFieldDirective(string $name, array $config): void
    {
        Blade::directive($name, function ($expression) use ($name, $config) {
            return $this->compileFieldDirective($name, $expression, $config);
        });
    }

    /**
     * Register a layout component directive
     */
    protected function registerLayoutComponentDirective(string $name, array $config): void
    {
        // Opening directive
        Blade::directive($name, function ($expression) use ($name, $config) {
            return $this->compileLayoutDirective($name, $expression, $config, 'open');
        });

        // Closing directive if needed
        if ($config['closing']) {
            Blade::directive('end' . Str::studly($name), function () use ($name) {
                return $this->compileLayoutDirective($name, '', [], 'close');
            });
        }
    }

    /**
     * Compile a field directive into PHP code
     */
    protected function compileFieldDirective(string $name, string $expression, array $config): string
    {
        return "<?php
            \$__fieldRegistry = app('" . static::class . "');
            \$__fieldRegistry->captureFieldDefinition('{$name}', {$expression}, " . var_export($config, true) . ');
        ?>';
    }

    /**
     * Compile a layout directive into PHP code
     */
    protected function compileLayoutDirective(string $name, string $expression, array $config, string $type): string
    {
        if ($type === 'open') {
            return "<?php
                \$__fieldRegistry = app('" . static::class . "');
                \$__fieldRegistry->openLayoutComponent('{$name}', {$expression}, " . var_export($config, true) . ');
                ob_start();
            ?>';
        } else {
            return "<?php
                \$__content = ob_get_clean();
                \$__fieldRegistry = app('" . static::class . "');
                \$__fieldRegistry->closeLayoutComponent('{$name}', \$__content);
            ?>";
        }
    }

    /**
     * Capture field definition for processing
     */
    public function captureFieldDefinition(string $type, string $expression, array $config): void
    {
        $this->fieldDefinitions[] = [
            'type'          => $type,
            'id'            => $this->parseId($expression),
            'options'       => $this->parseOptions($expression),
            'component'     => $config['component'],
            'section_stack' => array_slice($this->currentSectionStack, 0), // Copy current stack
        ];
    }

    /**
     * Open a layout component
     */
    public function openLayoutComponent(string $type, string $expression, array $config): void
    {
        $parsed = $this->parseDirectiveExpression($expression);

        $layoutDef = [
            'type'      => $type,
            'id'        => $parsed['id'] ?? uniqid('layout_'),
            'options'   => $parsed['options'] ?? [],
            'component' => $config['component'],
            'children'  => [],
        ];

        $this->currentSectionStack[] = $layoutDef;
    }

    /**
     * Close a layout component
     */
    public function closeLayoutComponent(string $type, string $content): void
    {
        if (! empty($this->currentSectionStack)) {
            $layoutDef = array_pop($this->currentSectionStack);

            // If we have a parent section, add this as a child
            if (! empty($this->currentSectionStack)) {
                $this->currentSectionStack[count($this->currentSectionStack) - 1]['children'][] = $layoutDef;
            } else {
                // This is a top-level layout component
                $this->layoutDefinitions[] = $layoutDef;
            }
        }
    }

    /**
     * Parse directive expression to extract id and options
     */
    protected function parseDirectiveExpression(string $expression): array
    {
        // Remove outer parentheses and trim
        $expression = trim($expression, '()');

        if (empty($expression)) {
            return ['id' => uniqid('field_'), 'options' => []];
        }

        // Try to extract id and options
        // Format: 'field_id', ['option' => 'value', ...]
        $parts = explode(',', $expression, 2);

        $id = trim($parts[0], '\'"');
        $options = [];

        if (isset($parts[1])) {
            // Parse the options array
            $optionsString = trim($parts[1]);
            $options = $this->parseOptionsString($optionsString);
        }

        return ['id' => $id, 'options' => $options];
    }

    /**
     * Parse options string into array
     */
    protected function parseOptionsString(string $optionsString): array
    {
        // For now, return empty array - in a real implementation you'd want
        // to properly parse the PHP array syntax
        // This would need a more sophisticated parser
        return [];
    }

    /**
     * Parse ID from expression
     */
    protected function parseId(string $expression): string
    {
        $parsed = $this->parseDirectiveExpression($expression);
        return $parsed['id'];
    }

    /**
     * Parse options from expression
     */
    protected function parseOptions(string $expression): array
    {
        $parsed = $this->parseDirectiveExpression($expression);
        return $parsed['options'];
    }

    /**
     * Get all captured field definitions
     */
    public function getFieldDefinitions(): array
    {
        return $this->fieldDefinitions;
    }

    /**
     * Get all captured layout definitions
     */
    public function getLayoutDefinitions(): array
    {
        return $this->layoutDefinitions;
    }

    /**
     * Clear all definitions (useful for processing multiple templates)
     */
    public function clearDefinitions(): void
    {
        $this->fieldDefinitions = [];
        $this->layoutDefinitions = [];
        $this->currentSectionStack = [];
    }

    /**
     * Convert captured definitions to Filament schema
     */
    public function toFilamentSchema(): array
    {
        $schema = [];

        // Add layout components with their children
        foreach ($this->layoutDefinitions as $layoutDef) {
            $schema[] = $this->buildFilamentComponent($layoutDef);
        }

        // Add standalone fields (not in any layout)
        foreach ($this->fieldDefinitions as $fieldDef) {
            if (empty($fieldDef['section_stack'])) {
                $schema[] = $this->buildFilamentComponent($fieldDef);
            }
        }

        return $schema;
    }

    /**
     * Build a Filament component from definition
     */
    protected function buildFilamentComponent(array $definition): array
    {
        return [
            'type'      => $definition['type'],
            'component' => $definition['component'],
            'id'        => $definition['id'],
            'options'   => $definition['options'],
            'children'  => $definition['children'] ?? [],
        ];
    }
}
