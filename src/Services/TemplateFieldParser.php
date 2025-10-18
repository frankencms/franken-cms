<?php

namespace FrankenCms\Services;

use Illuminate\Support\Facades\File;
use RuntimeException;
use Throwable;

class TemplateFieldParser
{
    /**
     * Parse a template file and extract all @cmsField directives
     *
     * @return array Array of field definitions
     */
    public function parseTemplate(string $templatePath): array
    {
        if (! File::exists($templatePath)) {
            return [];
        }

        $content = File::get($templatePath);

        return $this->parseContent($content);
    }

    /**
     * Parse template content and extract @cmsField directives
     *
     * @return array Array of field definitions
     */
    public function parseContent(string $content): array
    {
        $fields = [];
        $pattern = '/@cmsField\s*\(\s*([\'"])([^\'"]+)\1\s*,\s*([\'"])([^\'"]+)\3(?:\s*,\s*(\[.*?\]))?\s*\)/s';

        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $fieldName = $match[2];
            $fieldType = $match[4];
            $options = isset($match[5]) ? $this->parseOptions($match[5]) : [];

            // Check for duplicate field names
            if (isset($fields[$fieldName])) {
                throw new RuntimeException(
                    "Duplicate field name '{$fieldName}' found in template. Each field name must be unique."
                );
            }

            $fields[$fieldName] = [
                'name'    => $fieldName,
                'type'    => $fieldType,
                'options' => $options,
            ];
        }

        return $fields;
    }

    /**
     * Get fields organized by section (based on dot notation)
     */
    public function getFieldsBySection(array $fields): array
    {
        $sections = [];

        foreach ($fields as $field) {
            $fieldName = $field['name'];

            // Check if field uses dot notation
            if (str_contains($fieldName, '.')) {
                $parts = explode('.', $fieldName);
                $sectionName = $parts[0];
                $sections[$sectionName][] = $field;
            } else {
                // Fields without dot notation go into a "General" section
                $sections['general'][] = $field;
            }
        }

        return $sections;
    }

    /**
     * Validate that a template's fields are properly formatted
     */
    public function validateFields(array $fields): bool
    {
        foreach ($fields as $field) {
            if (empty($field['name']) || empty($field['type'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Parse the options array from the directive
     */
    protected function parseOptions(string $optionsString): array
    {
        // Remove square brackets
        $optionsString = trim($optionsString, '[]');

        if (empty($optionsString)) {
            return [];
        }

        // Use eval to parse the PHP array syntax safely within a controlled context
        // This is safe because we're only parsing template files, not user input
        try {
            $options = eval("return [{$optionsString}];");

            return is_array($options) ? $options : [];
        } catch (Throwable $e) {
            throw new RuntimeException(
                "Failed to parse field options: {$optionsString}. Error: {$e->getMessage()}"
            );
        }
    }
}
