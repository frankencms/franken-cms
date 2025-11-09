<?php

namespace FrankenCms\Factories;

use Exception;
use Filament\Schemas\Components\Section;
use FrankenCms\Filament\Schemas\ImageFieldSchema;
use FrankenCms\Services\FilamentFieldSchemaBuilder;
use FrankenCms\Services\TemplateFieldExtractor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TemplateFieldFactory
{
    public static function createFromTemplate(?string $templateName): array
    {
        if (is_null($templateName)) {
            return [];
        }

        $themeFolder = config('franken-cms.theme_folder');
        $templatePath = resource_path("views/{$themeFolder}/{$templateName}.blade.php");

        // Parse template and extract field definitions
        $extractor = app(TemplateFieldExtractor::class);
        $fields = $extractor->parseTemplate($templatePath);

        if (empty($fields)) {
            return [];
        }

        // Group fields by section (based on dot notation prefix)
        $sections = static::groupFieldsBySection($fields);

        $fieldBuilder = app(FilamentFieldSchemaBuilder::class);
        $fieldMapping = $fieldBuilder->getFieldTypeMap();
        $formComponents = [];

        foreach ($sections as $sectionName => $sectionFields) {
            $sectionFieldComponents = [];

            foreach ($sectionFields as $identifier => $field) {
                $type = $field['type'];
                $options = $field['options'] ?? [];

                // Special handling for image type (media_image is legacy alias)
                if (in_array($type, ['image', 'media_image'])) {
                    $collection = $options['collection'] ?? $identifier;

                    // ImageFieldSchema::make() returns an array of components
                    $imageComponents = ImageFieldSchema::make($identifier, $collection, $options);

                    // Add all components to the section
                    foreach ($imageComponents as $component) {
                        $sectionFieldComponents[] = $component;
                    }

                    continue;
                }

                // Use FilamentFieldSchemaBuilder to create the field
                $fieldDefinition = [
                    'name'    => $identifier,
                    'type'    => $type,
                    'options' => $options,
                ];

                try {
                    $fieldInstance = $fieldBuilder->buildField($fieldDefinition);
                    $sectionFieldComponents[] = $fieldInstance;
                } catch (Exception $e) {
                    Log::warning("Failed to build field '{$identifier}' of type '{$type}': {$e->getMessage()}");
                }
            }

            // Create a Filament Section for each group
            if ($sectionName === 'general') {
                // Don't wrap general fields in a section, add them directly
                $formComponents = array_merge($formComponents, $sectionFieldComponents);
            } else {
                $formComponents[] = Section::make(Str::title(str_replace(['-', '_'], ' ', $sectionName)))
                    ->schema($sectionFieldComponents)
                    ->columns(1)
                    ->collapsible();
            }
        }

        return $formComponents;
    }

    /**
     * Group fields by their section prefix (based on dot notation)
     */
    protected static function groupFieldsBySection(array $fields): array
    {
        $sections = [];

        foreach ($fields as $field) {
            $fieldName = $field['name'];

            // Check if field uses dot notation
            if (str_contains($fieldName, '.')) {
                $parts = explode('.', $fieldName, 2);
                $sectionName = $parts[0];
            } else {
                $sectionName = 'general';
            }

            $sections[$sectionName][$fieldName] = $field;
        }

        return $sections;
    }
}
