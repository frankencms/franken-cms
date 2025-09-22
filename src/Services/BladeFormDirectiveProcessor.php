<?php

declare(strict_types=1);

namespace FrankenCms\Services;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class BladeFormDirectiveProcessor
{
    protected BladeFormDirectiveRegistry $registry;

    public function __construct(BladeFormDirectiveRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Process a blade template to extract form field definitions
     */
    public function processTemplate(string $templateContent, string $templatePath = null): array
    {
        // Clear any previous definitions
        $this->registry->clearDefinitions();

        try {
            // Create a temporary view to compile the blade template
            $tempViewName = 'temp_form_processor_' . uniqid();

            // Store the original view content
            View::addNamespace('temp_processor', storage_path('framework/views'));

            // Create temporary file
            $tempPath = storage_path('framework/views/' . $tempViewName . '.blade.php');
            file_put_contents($tempPath, $templateContent);

            // Compile the view (this will trigger our directives)
            View::make("temp_processor::{$tempViewName}")->render();

            // Clean up
            unlink($tempPath);

            // Return the collected definitions
            return [
                'fields' => $this->registry->getFieldDefinitions(),
                'layouts' => $this->registry->getLayoutDefinitions(),
                'schema' => $this->registry->toFilamentSchema(),
            ];

        } catch (\Exception $e) {
            // Clean up on error
            if (isset($tempPath) && file_exists($tempPath)) {
                unlink($tempPath);
            }

            throw new \RuntimeException("Failed to process blade template: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Process a template file
     */
    public function processTemplateFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("Template file not found: {$filePath}");
        }

        $content = file_get_contents($filePath);
        return $this->processTemplate($content, $filePath);
    }

    /**
     * Convert form definitions to storable format
     */
    public function convertToStorableFormat(array $definitions): array
    {
        return [
            'version' => '1.0',
            'generated_at' => now()->toISOString(),
            'fields' => $this->flattenFieldsForStorage($definitions['fields']),
            'layouts' => $this->flattenLayoutsForStorage($definitions['layouts']),
            'schema' => $definitions['schema'],
        ];
    }

    /**
     * Flatten fields for storage
     */
    protected function flattenFieldsForStorage(array $fields): array
    {
        return array_map(function ($field) {
            return [
                'type' => $field['type'],
                'id' => $field['id'],
                'component' => $field['component'],
                'options' => $field['options'],
                'section_path' => $this->buildSectionPath($field['section_stack'] ?? []),
            ];
        }, $fields);
    }

    /**
     * Flatten layouts for storage
     */
    protected function flattenLayoutsForStorage(array $layouts): array
    {
        $flattened = [];

        foreach ($layouts as $layout) {
            $flattened[] = $this->flattenLayout($layout);
        }

        return $flattened;
    }

    /**
     * Recursively flatten a layout structure
     */
    protected function flattenLayout(array $layout, string $parentPath = ''): array
    {
        $path = $parentPath ? "{$parentPath}.{$layout['id']}" : $layout['id'];

        $result = [
            'type' => $layout['type'],
            'id' => $layout['id'],
            'component' => $layout['component'],
            'options' => $layout['options'],
            'path' => $path,
            'children_count' => count($layout['children'] ?? []),
        ];

        // Process children if they exist
        if (!empty($layout['children'])) {
            $result['children'] = [];
            foreach ($layout['children'] as $child) {
                $result['children'][] = $this->flattenLayout($child, $path);
            }
        }

        return $result;
    }

    /**
     * Build section path from stack
     */
    protected function buildSectionPath(array $sectionStack): string
    {
        if (empty($sectionStack)) {
            return '';
        }

        return collect($sectionStack)
            ->pluck('id')
            ->implode('.');
    }

    /**
     * Convert stored format back to Filament schema
     */
    public function convertStoredToFilamentSchema(array $storedData): array
    {
        if (!isset($storedData['schema'])) {
            return [];
        }

        return $storedData['schema'];
    }

    /**
     * Generate PHP code for Filament schema
     */
    public function generateFilamentSchemaCode(array $schema): string
    {
        $code = "[\n";

        foreach ($schema as $component) {
            $code .= $this->generateComponentCode($component, 1);
        }

        $code .= "]";

        return $code;
    }

    /**
     * Generate code for a single component
     */
    protected function generateComponentCode(array $component, int $indent = 0): string
    {
        $spaces = str_repeat('    ', $indent);
        $componentClass = class_basename($component['component']);

        $code = "{$spaces}{$componentClass}::make('{$component['id']}')\n";

        // Add options
        foreach ($component['options'] as $method => $value) {
            $formattedValue = $this->formatValue($value);
            $code .= "{$spaces}    ->{$method}({$formattedValue})\n";
        }

        // Add children if layout component
        if (!empty($component['children'])) {
            $code .= "{$spaces}    ->schema([\n";
            foreach ($component['children'] as $child) {
                $code .= $this->generateComponentCode($child, $indent + 2);
            }
            $code .= "{$spaces}    ])\n";
        }

        $code .= "{$spaces},\n";

        return $code;
    }

    /**
     * Format a value for PHP code generation
     */
    protected function formatValue($value): string
    {
        if (is_string($value)) {
            return "'" . addslashes($value) . "'";
        } elseif (is_bool($value)) {
            return $value ? 'true' : 'false';
        } elseif (is_array($value)) {
            return '[' . implode(', ', array_map([$this, 'formatValue'], $value)) . ']';
        } elseif (is_null($value)) {
            return 'null';
        } else {
            return (string) $value;
        }
    }

    /**
     * Extract field IDs from template for validation
     */
    public function extractFieldIds(string $templateContent): array
    {
        $fieldIds = [];

        // Pattern to match directive calls with IDs
        $pattern = '/@(?:textInput|textarea|select|toggle|checkbox|radio|dateTimePicker|datePicker|timePicker|fileUpload|richEditor|markdownEditor|keyValue|repeater|colorPicker)\s*\(\s*[\'"]([^\'\"]+)[\'\"]/';

        if (preg_match_all($pattern, $templateContent, $matches)) {
            $fieldIds = array_merge($fieldIds, $matches[1]);
        }

        return array_unique($fieldIds);
    }

    /**
     * Validate template for common issues
     */
    public function validateTemplate(string $templateContent): array
    {
        $errors = [];
        $warnings = [];

        // Check for unmatched section directives
        $openSections = substr_count($templateContent, '@section');
        $closeSections = substr_count($templateContent, '@endSection');

        if ($openSections !== $closeSections) {
            $errors[] = "Unmatched section directives: {$openSections} @section but {$closeSections} @endSection";
        }

        // Check for duplicate field IDs
        $fieldIds = $this->extractFieldIds($templateContent);
        $duplicates = array_diff_assoc($fieldIds, array_unique($fieldIds));

        if (!empty($duplicates)) {
            $errors[] = "Duplicate field IDs found: " . implode(', ', array_unique($duplicates));
        }

        // Check for empty field IDs
        if (preg_match('/@(?:textInput|textarea|select|toggle)[^(]*\(\s*[\'\"]\s*[\'\"]/m', $templateContent)) {
            $warnings[] = "Empty field IDs detected - these will be auto-generated";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }
}