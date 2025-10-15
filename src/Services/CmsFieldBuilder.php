<?php

namespace FrankenCms\Services;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class CmsFieldBuilder
{
    /**
     * Map of field types to Filament component classes
     */
    protected array $fieldTypeMap = [
        'text' => TextInput::class,
        'textarea' => Textarea::class,
        'email' => TextInput::class,
        'url' => TextInput::class,
        'number' => TextInput::class,
        'select' => Select::class,
        'file' => FileUpload::class,
        'image' => FileUpload::class,
        'repeater' => Repeater::class,
        'richEditor' => RichEditor::class,
        'toggle' => Toggle::class,
        'checkbox' => Checkbox::class,
    ];

    /**
     * Build a Filament form component from field definition
     */
    public function buildField(array $fieldDefinition): mixed
    {
        $name = $fieldDefinition['name'];
        $type = $fieldDefinition['type'];
        $options = $fieldDefinition['options'] ?? [];

        // Get the component class
        $componentClass = $this->fieldTypeMap[$type] ?? TextInput::class;

        // Create the component with the field name prefixed for storage in custom_fields
        $field = $componentClass::make("custom_fields.{$name}");

        // Apply options as method calls
        foreach ($options as $method => $value) {
            if (method_exists($field, $method)) {
                // Handle special cases
                if (is_array($value)) {
                    $field->{$method}(...$value);
                } else {
                    $field->{$method}($value);
                }
            }
        }

        // Apply type-specific defaults
        $field = $this->applyTypeSpecificDefaults($field, $type, $options);

        return $field;
    }

    /**
     * Apply type-specific default configurations
     */
    protected function applyTypeSpecificDefaults(mixed $field, string $type, array $options): mixed
    {
        match ($type) {
            'email' => $field instanceof TextInput ? $field->email() : null,
            'url' => $field instanceof TextInput ? $field->url() : null,
            'number' => $field instanceof TextInput ? $field->numeric() : null,
            'image' => $field instanceof FileUpload ? $field->image()->imageEditor() : null,
            default => null,
        };

        return $field;
    }

    /**
     * Build all fields from an array of field definitions
     *
     * @return array<mixed>
     */
    public function buildFields(array $fieldDefinitions): array
    {
        $fields = [];

        foreach ($fieldDefinitions as $fieldDefinition) {
            $fields[] = $this->buildField($fieldDefinition);
        }

        return $fields;
    }

    /**
     * Register a custom field type mapping
     */
    public function registerFieldType(string $type, string $componentClass): void
    {
        $this->fieldTypeMap[$type] = $componentClass;
    }

    /**
     * Get all registered field type mappings
     */
    public function getFieldTypeMap(): array
    {
        return $this->fieldTypeMap;
    }
}
