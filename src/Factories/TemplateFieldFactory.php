<?php

namespace FrankenCms\Factories;

use FilamentTiptapEditor\Enums\TiptapOutput;
use FrankenCms\Registries\FieldRegistry;
use FrankenCms\Services\CmsFieldParser;
use Illuminate\Support\Facades\Log;

class TemplateFieldFactory
{
    public static function createFromTemplate(?string $templateName): array
    {
        if (is_null($templateName)) {
            return [];
        }

        $templateFolder = config('franken-cms.template_folder');
        $templatePath = resource_path("views/{$templateFolder}/{$templateName}.blade.php");

        $parser = new CmsFieldParser;
        $parser->parse($templatePath);

        // Retrieve fields from the Field Registry.
        $fields = FieldRegistry::getFields();

        $fieldMapping = config('franken-cms.cms_fields');
        $formFields = [];

        foreach ($fields as $identifier => $field) {
            $type = $field['type'];
            $properties = $field['properties'];

            // Check if this field type has a mapped Filament class
            if (! isset($fieldMapping[$type])) {
                Log::warning("No mapping found for field type: {$type}");
                continue;
            }

            // Get the class from the mapping and create an instance
            $filamentClass = $fieldMapping[$type];
            $fieldInstance = $filamentClass::make("post_content.{$identifier}");

            // Add special properties for specific field types

            // TODO: Update to use new editor

            //            if ($type === 'TipTapEditor') {
            //                $fieldInstance = $fieldInstance->output(TiptapOutput::Json);
            //            }

            // Configure field properties if supported
            foreach ($properties as $method => $value) {
                if (method_exists($fieldInstance, $method)) {
                    $fieldInstance = $fieldInstance->$method($value);
                }
            }

            $formFields[] = $fieldInstance;
        }

        return $formFields;
    }
}
