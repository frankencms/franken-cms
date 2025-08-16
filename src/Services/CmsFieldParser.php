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

        // Regex pattern to match @cmsField directives.
        $pattern = '/@cmsField\(\s*([\'"])(.*?)\1\s*,\s*([\'"])(.*?)\3\s*,\s*(\[[^\)]*\])\s*\)/ms';

        // Regex pattern to match anonymous Blade component directives.
        $anonymousPattern = '/<x-cms-field\s+name="([^"]+)"\s+type="([^"]+)"\s+:\s*properties="([^"]+)"\s*\/>/ms';

        // Match and process @cmsField directives.
        if (preg_match_all($pattern, $templateContent, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $identifier = $match[2];
                $type = $match[4];
                $arrayCode = $match[5];

                // Evaluate the array code safely.
                $properties = eval("return {$arrayCode};");
                FieldRegistry::register($identifier, $type, $properties);
            }
        }

        // Match and process anonymous Blade component directives.
        if (preg_match_all($anonymousPattern, $templateContent, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $identifier = $match[1];
                $type = $match[2];
                $arrayCode = $match[3];

                // Evaluate the array code safely.
                $properties = eval("return {$arrayCode};");
                FieldRegistry::register($identifier, $type, $properties);
            }
        }

    }
}
