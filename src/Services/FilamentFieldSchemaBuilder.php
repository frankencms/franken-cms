<?php

namespace FrankenCms\Services;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use FrankenCms\Filament\Schemas\ImageFieldSchema;
use FrankenCms\Registries\FieldTypeRegistry;
use InvalidArgumentException;

class FilamentFieldSchemaBuilder
{
    /**
     * Custom component class overrides
     */
    protected array $customComponents = [];

    public function __construct(
        protected FieldTypeRegistry $fieldTypeRegistry
    ) {}

    /**
     * Build a Filament form component from field definition
     *
     * Note: For image fields, this returns an array of components (schema), not a single component
     */
    public function buildField(array $fieldDefinition): mixed
    {
        $name = $fieldDefinition['name'];
        $type = $fieldDefinition['type'];
        $options = $fieldDefinition['options'] ?? [];

        // Image fields use the comprehensive ImageFieldSchema
        if ($type === 'image') {
            // Use the field name as the collection name
            return ImageFieldSchema::make(
                fieldName: $name,
                collection: $name,
                options: $options
            );
        }

        // Check for custom component override first
        if (isset($this->customComponents[$type])) {
            $componentClass = $this->customComponents[$type];
        } else {
            // Get component class from registry
            $fieldTypeDefinition = $this->fieldTypeRegistry->get($type);
            $componentClass = $fieldTypeDefinition?->getFilamentComponentClass() ?? TextInput::class;
        }

        // All fields (except images) are stored in custom_fields JSON
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
        $field = $this->applyTypeSpecificDefaults($field, $type, $name, $options);

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
            $result = $this->buildField($fieldDefinition);

            // Image fields return an array of components, flatten them
            if (is_array($result) && ! $result instanceof Component) {
                foreach ($result as $component) {
                    $fields[] = $component;
                }
            } else {
                $fields[] = $result;
            }
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
     * Register a custom component class override
     */
    public function registerFieldType(string $type, string $componentClass): void
    {
        $this->customComponents[$type] = $componentClass;
    }

    /**
     * Get all registered field type mappings (from registry and custom overrides)
     */
    public function getFieldTypeMap(): array
    {
        $mappings = [];

        // Get mappings from registry
        foreach ($this->fieldTypeRegistry->all() as $fieldType) {
            $mappings[$fieldType->getName()] = $fieldType->getFilamentComponentClass();
        }

        // Merge custom overrides
        return array_merge($mappings, $this->customComponents);
    }

    /**
     * Apply type-specific default configurations
     */
    protected function applyTypeSpecificDefaults(mixed $field, string $type, string $name, array $options): mixed
    {
        match ($type) {
            'email'  => $field instanceof TextInput ? $field->email() : null,
            'url'    => $field instanceof TextInput ? $field->url() : null,
            'number' => $field instanceof TextInput ? $field->numeric() : null,
            default  => null,
        };

        return $field;
    }
}
