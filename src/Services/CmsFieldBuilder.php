<?php

namespace FrankenCms\Services;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use InvalidArgumentException;

class CmsFieldBuilder
{
    /**
     * Map of field types to Filament component classes
     */
    protected array $fieldTypeMap = [
        'text'       => TextInput::class,
        'textarea'   => Textarea::class,
        'email'      => TextInput::class,
        'url'        => TextInput::class,
        'number'     => TextInput::class,
        'select'     => Select::class,
        'file'       => FileUpload::class,
        'image'      => FileUpload::class,
        'repeater'   => Repeater::class,
        'richEditor' => RichEditor::class,
        'toggle'     => Toggle::class,
        'checkbox'   => Checkbox::class,
        'tags'       => TagsInput::class,
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
     * Build a schema field - supports both Filament Component instances and array definitions
     *
     * This method checks if the provided value is already a Filament Component instance,
     * or if it's an array definition that needs to be built into a component.
     *
     * @param  mixed  $fieldDefinition  Either a Component instance or an array with field definition
     * @return mixed The built or passed-through component
     */
    public function buildSchemaField(mixed $fieldDefinition): mixed
    {
        // If it's already a Filament Component, return it as-is
        if ($fieldDefinition instanceof Component) {
            return $fieldDefinition;
        }

        // If it's an array, build it using our field builder
        if (is_array($fieldDefinition)) {
            // Ensure 'name' key exists
            if (! isset($fieldDefinition['name'])) {
                throw new InvalidArgumentException('Field definition array must have a "name" key');
            }

            // Default type to 'text' if not specified
            if (! isset($fieldDefinition['type'])) {
                $fieldDefinition['type'] = 'text';
            }

            // Move all keys except 'name' and 'type' into 'options'
            $name = $fieldDefinition['name'];
            $type = $fieldDefinition['type'];
            $options = array_diff_key($fieldDefinition, array_flip(['name', 'type']));

            return $this->buildField([
                'name'    => $name,
                'type'    => $type,
                'options' => $options,
            ]);
        }

        throw new InvalidArgumentException('Field definition must be either a Component instance or an array');
    }

    /**
     * Build an array of schema fields - supports mixed array of Components and array definitions
     *
     * @param  array  $schemaDefinitions  Array of Component instances and/or array definitions
     * @return array<mixed>
     */
    public function buildSchema(array $schemaDefinitions): array
    {
        $schema = [];

        foreach ($schemaDefinitions as $fieldDefinition) {
            $schema[] = $this->buildSchemaField($fieldDefinition);
        }

        return $schema;
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

    /**
     * Apply type-specific default configurations
     */
    protected function applyTypeSpecificDefaults(mixed $field, string $type, array $options): mixed
    {
        match ($type) {
            'email'  => $field instanceof TextInput ? $field->email() : null,
            'url'    => $field instanceof TextInput ? $field->url() : null,
            'number' => $field instanceof TextInput ? $field->numeric() : null,
            'image'  => $field instanceof FileUpload ? $field->image()->imageEditor() : null,
            default  => null,
        };

        return $field;
    }
}
