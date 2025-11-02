<?php

namespace FrankenCms\Services;

use Exception;
use FrankenCms\Registries\FieldRegistry;
use Log;
use Throwable;

class CmsFieldParser
{
    /**
     * Parses a Blade template for @franken field directives and registers them.
     *
     * @param  string  $templatePath  The full path to the Blade file.
     *
     * @throws Exception
     */
    public function parse(string $templatePath): void
    {
        // Reset the registry to avoid duplicate entries.
        FieldRegistry::reset();

        // Read the template file.
        $templateContent = file_get_contents($templatePath);
        if ($templateContent === false) {
            throw new Exception("Unable to read template file: {$templatePath}");
        }

        // Parse @franken directives using a more robust method
        $this->parseDirectives($templateContent);
    }

    /**
     * Parse @franken field directives from template content
     */
    protected function parseDirectives(string $content): void
    {
        // List of all typed field directives
        $directiveTypes = [
            'Text', 'Textarea', 'Email', 'Url', 'Number', 'Select',
            'File', 'Image', 'MediaImage', 'RichEditor', 'Toggle',
            'Checkbox', 'Tags', 'Repeater'
        ];

        // Find all @franken* directives
        foreach ($directiveTypes as $type) {
            $directive = "@franken{$type}(";
            $directiveLen = strlen($directive);
            $offset = 0;

            while (($pos = strpos($content, $directive, $offset)) !== false) {
                // Move past '@franken*('
                $start = $pos + $directiveLen;

                // Extract the directive arguments by finding balanced parentheses
                $result = $this->extractBalancedParentheses($content, $start);

                if ($result === null) {
                    $offset = $start;
                    continue;
                }

                [$arguments, $endPos] = $result;

                // Parse the extracted arguments with the directive type
                $this->parseFieldArguments($arguments, $type);

                $offset = $endPos;
            }
        }
    }

    /**
     * Extract content within balanced parentheses
     */
    protected function extractBalancedParentheses(string $content, int $start): ?array
    {
        $depth = 1;
        $length = strlen($content);
        $inString = false;
        $stringChar = null;
        $escaped = false;

        for ($i = $start; $i < $length; $i++) {
            $char = $content[$i];

            // Handle escape sequences
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

            // Only count parentheses outside of strings
            if (! $inString) {
                if ($char === '(') {
                    $depth++;
                } elseif ($char === ')') {
                    $depth--;
                    if ($depth === 0) {
                        // Found the closing parenthesis
                        return [substr($content, $start, $i - $start), $i + 1];
                    }
                }
            }
        }

        return null;
    }

    /**
     * Parse field arguments (name, options) from typed directive
     *
     * @param string $arguments The arguments string from the directive
     * @param string $directiveType The directive type (e.g., 'Text', 'Repeater')
     */
    protected function parseFieldArguments(string $arguments, string $directiveType): void
    {
        // Convert directive type to field type (Text -> text, MediaImage -> image)
        $fieldType = $this->getFieldTypeFromDirective($directiveType);

        // Match: 'fieldname', [options]
        if (preg_match('/^\s*([\'"])(.*?)\1\s*,\s*(.+)$/s', $arguments, $matches)) {
            // Has options array
            $identifier = $matches[2];
            $arrayCode = trim($matches[3]);

            try {
                // Evaluate the array code safely.
                $properties = eval("return {$arrayCode};");

                if (is_array($properties)) {
                    FieldRegistry::register($identifier, $fieldType, $properties);
                }
            } catch (Throwable $e) {
                // Log error but continue parsing other fields
                Log::warning("Failed to parse field options for '{$identifier}': " . $e->getMessage());
            }
        } elseif (preg_match('/^\s*([\'"])(.*?)\1\s*$/s', $arguments, $matches)) {
            // No options array
            $identifier = $matches[2];
            FieldRegistry::register($identifier, $fieldType, []);
        }
    }

    /**
     * Get the field type from a directive suffix
     * Maps directive names to field types (same mapping as TemplateFieldParser)
     */
    protected function getFieldTypeFromDirective(string $directiveType): string
    {
        $mapping = [
            'Text'       => 'text',
            'Textarea'   => 'textarea',
            'Email'      => 'email',
            'Url'        => 'url',
            'Number'     => 'number',
            'Select'     => 'select',
            'File'       => 'file',
            'Image'      => 'image',
            'MediaImage' => 'image',
            'RichEditor' => 'richEditor',
            'Toggle'     => 'toggle',
            'Checkbox'   => 'checkbox',
            'Tags'       => 'tags',
            'Repeater'   => 'repeater',
        ];

        return $mapping[$directiveType] ?? lcfirst($directiveType);
    }
}
