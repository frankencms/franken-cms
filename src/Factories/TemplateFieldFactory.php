<?php

namespace FrankenCms\Factories;

use Filament\Schemas\Components\Section;
use FilamentTiptapEditor\Enums\TiptapOutput;
use FrankenCms\Registries\FieldRegistry;
use FrankenCms\Services\CmsFieldBuilder;
use FrankenCms\Services\CmsFieldParser;
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

        $parser = new CmsFieldParser;
        $parser->parse($templatePath);

        // Retrieve fields from the Field Registry.
        $fields = FieldRegistry::getFields();

        if (empty($fields)) {
            return [];
        }

        // Group fields by section (based on dot notation prefix)
        $sections = static::groupFieldsBySection($fields);

        $fieldMapping = config('franken-cms.cms_fields');
        $formComponents = [];

        foreach ($sections as $sectionName => $sectionFields) {
            $sectionFieldComponents = [];

            foreach ($sectionFields as $identifier => $field) {
                $type = $field['type'];
                $properties = $field['properties'];

                // Check if this field type has a mapped Filament class
                if (! isset($fieldMapping[$type])) {
                    Log::warning("No mapping found for field type: {$type}");
                    continue;
                }

                // Get the class from the mapping and create an instance
                $filamentClass = $fieldMapping[$type];
                $fieldInstance = $filamentClass::make("custom_fields.{$identifier}");

                // Configure field properties if supported
                foreach ($properties as $method => $value) {
                    if (method_exists($fieldInstance, $method)) {
                        // Special handling for 'schema' - build fields from mixed definitions
                        if ($method === 'schema' && is_array($value)) {
                            $fieldBuilder = app(CmsFieldBuilder::class);
                            $builtSchema = $fieldBuilder->buildSchema($value);
                            $fieldInstance = $fieldInstance->schema($builtSchema);
                        }
                        // Handle other array values that should be spread
                        elseif (is_array($value) && in_array($method, ['columns'])) {
                            $fieldInstance = $fieldInstance->$method($value);
                        } else {
                            $fieldInstance = $fieldInstance->$method($value);
                        }
                    }
                }

                $sectionFieldComponents[] = $fieldInstance;
            }

            // Create a Filament Section for each group
            if ($sectionName === 'general') {
                // Don't wrap general fields in a section, add them directly
                $formComponents = array_merge($formComponents, $sectionFieldComponents);
            } else {
                $formComponents[] = Section::make(Str::title(str_replace(['-', '_'], ' ', $sectionName)))
                    ->schema($sectionFieldComponents)
                    ->columns(2)
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

        foreach ($fields as $identifier => $field) {
            // Check if field uses dot notation
            if (str_contains($identifier, '.')) {
                $parts = explode('.', $identifier, 2);
                $sectionName = $parts[0];
            } else {
                $sectionName = 'general';
            }

            $sections[$sectionName][$identifier] = $field;
        }

        return $sections;
    }
}
