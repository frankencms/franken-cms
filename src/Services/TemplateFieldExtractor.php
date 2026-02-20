<?php

namespace FrankenCms\Services;

use FrankenCms\Registries\FieldTypeRegistry;
use Illuminate\Support\Facades\File;
use RuntimeException;

class TemplateFieldExtractor
{
    public function __construct(
        protected FieldTypeRegistry $fieldTypeRegistry
    ) {}

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

        // Build directive pattern from registry
        $directiveNames = [];
        foreach ($this->fieldTypeRegistry->all() as $fieldType) {
            $directiveNames = array_merge($directiveNames, $fieldType->getDirectiveNames());
        }
        $directiveTypes = implode('|', $directiveNames);
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
     * Get the field type from a directive name using the registry
     */
    protected function getFieldTypeFromDirective(string $directiveType): string
    {
        $fieldType = $this->fieldTypeRegistry->getByDirective($directiveType);

        if ($fieldType === null) {
            // Fallback for unknown directives
            return lcfirst($directiveType);
        }

        return $fieldType->getName();
    }

    /**
     * Parse the options array from the directive without using eval()
     *
     * Handles: quoted strings, numbers, booleans, null, and nested arrays.
     * Throws on closures, function calls, or other PHP expressions.
     */
    protected function parseOptions(string $optionsString): array
    {
        $optionsString = trim($optionsString, '[]');

        if (empty(trim($optionsString))) {
            return [];
        }

        $pos = 0;

        return $this->parseArray($optionsString, $pos);
    }

    /**
     * Recursively parse a PHP-style array from a string
     */
    private function parseArray(string $input, int &$pos): array
    {
        $result = [];
        $length = strlen($input);
        $index = 0;

        while ($pos < $length) {
            $this->skipWhitespace($input, $pos);

            if ($pos >= $length) {
                break;
            }

            // Check for nested array closing
            if ($input[$pos] === ']') {
                $pos++; // consume closing bracket
                break;
            }

            // Skip commas between elements
            if ($input[$pos] === ',') {
                $pos++;
                continue;
            }

            // Parse value or key => value
            $value = $this->parseValue($input, $pos);

            $this->skipWhitespace($input, $pos);

            // Check if this is a key => value pair
            if ($pos + 1 < $length && $input[$pos] === '=' && $input[$pos + 1] === '>') {
                $pos += 2; // skip =>
                $this->skipWhitespace($input, $pos);
                $key = $value;
                $value = $this->parseValue($input, $pos);
                $result[$key] = $value;
            } else {
                $result[$index++] = $value;
            }
        }

        return $result;
    }

    /**
     * Parse a single value: string, number, boolean, null, or nested array
     */
    private function parseValue(string $input, int &$pos): mixed
    {
        $this->skipWhitespace($input, $pos);

        if ($pos >= strlen($input)) {
            throw new RuntimeException('Unexpected end of options string');
        }

        $char = $input[$pos];

        // Quoted string
        if ($char === "'" || $char === '"') {
            return $this->parseString($input, $pos);
        }

        // Nested array
        if ($char === '[') {
            $pos++; // skip opening bracket
            return $this->parseArray($input, $pos);
        }

        // Keyword or number — read until delimiter
        $start = $pos;
        while ($pos < strlen($input) && ! in_array($input[$pos], [',', ']', '=', ' ', "\t", "\n", "\r"], true)) {
            $pos++;
        }

        $token = trim(substr($input, $start, $pos - $start));

        return match (true) {
            $token === 'true'                                 => true,
            $token === 'false'                                => false,
            $token === 'null'                                 => null,
            is_numeric($token) && ! str_contains($token, '.') => (int) $token,
            is_numeric($token)                                => (float) $token,
            default                                           => throw new RuntimeException(
                "Unsupported expression in field options: '{$token}'. Only strings, numbers, booleans, null, and arrays are allowed."
            ),
        };
    }

    /**
     * Parse a quoted string, handling escape sequences
     */
    private function parseString(string $input, int &$pos): string
    {
        $quote = $input[$pos];
        $pos++; // skip opening quote
        $result = '';
        $length = strlen($input);

        while ($pos < $length) {
            $char = $input[$pos];

            if ($char === '\\' && $pos + 1 < $length) {
                $next = $input[$pos + 1];
                $result .= match ($next) {
                    '\\'    => '\\',
                    $quote  => $quote,
                    'n'     => "\n",
                    't'     => "\t",
                    default => '\\' . $next,
                };
                $pos += 2;
                continue;
            }

            if ($char === $quote) {
                $pos++; // skip closing quote
                return $result;
            }

            $result .= $char;
            $pos++;
        }

        throw new RuntimeException('Unterminated string in field options');
    }

    /**
     * Skip whitespace characters
     */
    private function skipWhitespace(string $input, int &$pos): void
    {
        while ($pos < strlen($input) && in_array($input[$pos], [' ', "\t", "\n", "\r"], true)) {
            $pos++;
        }
    }
}
