<?php

namespace FrankenCms\Services;

use Illuminate\Support\Facades\File;
use RuntimeException;
use Throwable;

class TemplateFieldParser
{
    /**
     * Parse a template file and extract all field directives
     *
     * Supports typed directives: @frankenText, @frankenTextarea, @frankenRepeater, etc.
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
     * Parse template content and extract field directives
     *
     * Supports typed directives: @frankenText, @frankenTextarea, @frankenRepeater, etc.
     *
     * @return array Array of field definitions
     */
    public function parseContent(string $content): array
    {
        $fields = [];

        // Pattern for new typed directives - match directive name and opening paren
        $directiveTypes = 'Text|Textarea|Email|Url|Number|Select|File|Image|MediaImage|RichEditor|Toggle|Checkbox|Tags|Repeater';
        $directivePattern = '/@franken(' . $directiveTypes . ')\s*\(/';

        // Find all directive positions
        if (preg_match_all($directivePattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $index => $match) {
                $directiveType = $matches[1][$index][0];
                $startPos = $match[1] + strlen($match[0]); // Position after opening (

                // Find the matching closing parenthesis
                $params = $this->extractBalancedParentheses($content, $startPos);

                if ($params !== null) {
                    // Parse the parameters
                    $parsed = $this->parseDirectiveParams($params);

                    if ($parsed && isset($parsed['fieldName'])) {
                        $fieldName = $parsed['fieldName'];
                        $options = $parsed['options'] ?? [];

                        // Convert directive type to field type
                        $fieldType = $this->getFieldTypeFromDirective($directiveType);

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
                }
            }
        }

        return $fields;
    }

    /**
     * Extract content between balanced parentheses
     */
    protected function extractBalancedParentheses(string $content, int $startPos): ?string
    {
        $depth = 1;
        $length = strlen($content);
        $result = '';

        for ($i = $startPos; $i < $length; $i++) {
            $char = $content[$i];

            if ($char === '(') {
                $depth++;
                $result .= $char;
            } elseif ($char === ')') {
                $depth--;
                if ($depth === 0) {
                    return $result;
                }
                $result .= $char;
            } else {
                $result .= $char;
            }
        }

        return null; // Unbalanced parentheses
    }

    /**
     * Parse parameters from typed directive: 'field.name', [options], placeholder (optional)
     */
    protected function parseDirectiveParams(string $params): ?array
    {
        // Match the field name (first quoted string)
        if (! preg_match('/^\s*([\'"])([^\'"]+)\1/', $params, $nameMatch)) {
            return null;
        }

        $fieldName = $nameMatch[2];
        $afterName = substr($params, strlen($nameMatch[0]));

        // Check if there are options (look for comma followed by opening bracket)
        if (preg_match('/^\s*,\s*(\[)/s', $afterName, $startMatch)) {
            // Find the position where the array starts
            $arrayStart = strpos($afterName, '[');

            // Extract balanced array using bracket counting
            $optionsString = $this->extractBalancedBrackets(substr($afterName, $arrayStart));

            if ($optionsString) {
                $options = $this->parseOptions($optionsString);
            } else {
                $options = [];
            }
        } else {
            $options = [];
        }

        // Note: We ignore any third parameter (placeholder boolean) as it's only used at runtime

        return [
            'fieldName' => $fieldName,
            'options'   => $options,
        ];
    }

    /**
     * Extract a balanced bracket expression from a string
     */
    protected function extractBalancedBrackets(string $content): ?string
    {
        if (! str_starts_with($content, '[')) {
            return null;
        }

        $depth = 0;
        $inString = false;
        $stringChar = null;
        $escaped = false;

        for ($i = 0; $i < strlen($content); $i++) {
            $char = $content[$i];

            if ($escaped) {
                $escaped = false;
                continue;
            }

            if ($char === '\\') {
                $escaped = true;
                continue;
            }

            // Handle string boundaries
            if (($char === '"' || $char === "'") && ! $inString) {
                $inString = true;
                $stringChar = $char;
                continue;
            }

            if ($inString && $char === $stringChar) {
                $inString = false;
                $stringChar = null;
                continue;
            }

            // Only count brackets outside of strings
            if (! $inString) {
                if ($char === '[') {
                    $depth++;
                } elseif ($char === ']') {
                    $depth--;
                    if ($depth === 0) {
                        // Found the closing bracket
                        return substr($content, 0, $i + 1);
                    }
                }
            }
        }

        return null;
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
     * Get the field type from a directive suffix
     * Maps directive names to field types (same mapping as service provider)
     */
    protected function getFieldTypeFromDirective(string $directiveType): string
    {
        // This mapping must match the one in FrankenCmsServiceProvider::registerTypedFieldDirectives()
        $mapping = [
            'Text'       => 'text',
            'Textarea'   => 'textarea',
            'Email'      => 'email',
            'Url'        => 'url',
            'Number'     => 'number',
            'Select'     => 'select',
            'File'       => 'file',
            'Image'      => 'image',
            'MediaImage' => 'image',  // Both Image and MediaImage map to 'image'
            'RichEditor' => 'richEditor',
            'Toggle'     => 'toggle',
            'Checkbox'   => 'checkbox',
            'Tags'       => 'tags',
            'Repeater'   => 'repeater',
        ];

        return $mapping[$directiveType] ?? lcfirst($directiveType);
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
