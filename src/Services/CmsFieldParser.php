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
     * Fields are registered in the order they appear in the template
     */
    protected function parseDirectives(string $content): void
    {
        // List of all typed field directives
        $directiveTypes = [
            'Text', 'Textarea', 'Email', 'Url', 'Number', 'Select',
            'File', 'Image', 'MediaImage', 'RichEditor', 'Toggle',
            'Checkbox', 'Tags', 'Repeater',
        ];

        // Find ALL directives with their positions
        $allDirectives = [];

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

                // Store directive with its position in the template
                $allDirectives[] = [
                    'position'  => $pos,
                    'type'      => $type,
                    'arguments' => $arguments,
                ];

                $offset = $endPos;
            }
        }

        // Sort by position to maintain template order
        usort($allDirectives, fn ($a, $b) => $a['position'] <=> $b['position']);

        // Parse in order of appearance
        foreach ($allDirectives as $directive) {
            $this->parseFieldArguments($directive['arguments'], $directive['type']);
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
     * Parse field arguments (name, options, placeholder) from typed directive
     *
     * @param  string  $arguments  The arguments string from the directive
     * @param  string  $directiveType  The directive type (e.g., 'Text', 'Repeater')
     */
    protected function parseFieldArguments(string $arguments, string $directiveType): void
    {
        // Convert directive type to field type (Text -> text, MediaImage -> image)
        $fieldType = $this->getFieldTypeFromDirective($directiveType);

        // Extract field name first
        if (! preg_match('/^\s*([\'"])(.*?)\1/', $arguments, $nameMatch)) {
            return;
        }

        $identifier = $nameMatch[2];
        $afterName = substr($arguments, strlen($nameMatch[0]));

        // Check if there are options (array after field name)
        if (preg_match('/^\s*,\s*\[/', $afterName)) {
            $arrayStart = strpos($afterName, '[');
            $arrayCode = $this->extractBalancedArray(substr($afterName, $arrayStart));

            if ($arrayCode) {
                try {
                    // Evaluate the array code safely (ignoring any third parameter)
                    $properties = eval("return {$arrayCode};");

                    if (is_array($properties)) {
                        FieldRegistry::register($identifier, $fieldType, $properties);
                    }
                } catch (Throwable $e) {
                    // Log error but continue parsing other fields
                    Log::warning("Failed to parse field options for '{$identifier}': " . $e->getMessage());
                }
            } else {
                FieldRegistry::register($identifier, $fieldType, []);
            }
        } else {
            // No options array
            FieldRegistry::register($identifier, $fieldType, []);
        }
    }

    /**
     * Extract balanced array from string (handles nested arrays)
     */
    protected function extractBalancedArray(string $content): ?string
    {
        if (! str_starts_with($content, '[')) {
            return null;
        }

        $depth = 0;
        $inString = false;
        $stringChar = null;

        for ($i = 0; $i < strlen($content); $i++) {
            $char = $content[$i];

            // Handle string boundaries
            if (! $inString && ($char === '"' || $char === "'")) {
                $inString = true;
                $stringChar = $char;
            } elseif ($inString && $char === $stringChar && ($i === 0 || $content[$i - 1] !== '\\')) {
                $inString = false;
                $stringChar = null;
            } elseif (! $inString) {
                if ($char === '[') {
                    $depth++;
                } elseif ($char === ']') {
                    $depth--;
                    if ($depth === 0) {
                        return substr($content, 0, $i + 1);
                    }
                }
            }
        }

        return null;
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
