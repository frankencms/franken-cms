<?php

namespace FrankenCms\Services;

use Exception;
use FrankenCms\Registries\FieldRegistry;

class CmsFieldParser
{
    /**
     * Parses a Blade template for @cmsField directives and registers them.
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

        // Parse @cmsField directives using a more robust method
        $this->parseDirectives($templateContent);
    }

    /**
     * Parse @cmsField directives from template content
     */
    protected function parseDirectives(string $content): void
    {
        // Find all @cmsField( occurrences
        $offset = 0;
        while (($pos = strpos($content, '@cmsField(', $offset)) !== false) {
            // Move past '@cmsField('
            $start = $pos + 10;

            // Extract the directive arguments by finding balanced parentheses
            $result = $this->extractBalancedParentheses($content, $start);

            if ($result === null) {
                $offset = $start;
                continue;
            }

            [$arguments, $endPos] = $result;

            // Parse the extracted arguments
            $this->parseFieldArguments($arguments);

            $offset = $endPos;
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
            if (($char === '"' || $char === "'") && !$inString) {
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
            if (!$inString) {
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
     * Parse field arguments (name, type, options)
     */
    protected function parseFieldArguments(string $arguments): void
    {
        // Match: 'name', 'type', [options]
        // The pattern now handles the first two quoted strings, then everything else is the options
        if (preg_match('/^\s*([\'"])(.*?)\1\s*,\s*([\'"])(.*?)\3\s*,\s*(.+)$/s', $arguments, $matches)) {
            $identifier = $matches[2];
            $type = $matches[4];
            $arrayCode = trim($matches[5]);

            try {
                // Evaluate the array code safely.
                $properties = eval("return {$arrayCode};");

                if (is_array($properties)) {
                    FieldRegistry::register($identifier, $type, $properties);
                }
            } catch (\Throwable $e) {
                // Log error but continue parsing other fields
                \Log::warning("Failed to parse CMS field options for '{$identifier}': " . $e->getMessage());
            }
        } elseif (preg_match('/^\s*([\'"])(.*?)\1\s*,\s*([\'"])(.*?)\3\s*$/s', $arguments, $matches)) {
            // Handle case with no options array
            $identifier = $matches[2];
            $type = $matches[4];
            FieldRegistry::register($identifier, $type, []);
        }
    }
}
