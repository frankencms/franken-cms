<?php

use FrankenCms\Helpers\TemplateHelper;

if (! function_exists('cmsField')) {
    /**
     * Render a CMS field value from the current page
     *
     * @param  string  $fieldName  The field name (supports dot notation)
     * @param  string  $fieldType  The field type (text, textarea, repeater, etc.)
     * @param  array  $options  Additional options (not used for rendering, only for admin)
     * @return mixed The rendered field value
     */
    function cmsField(string $fieldName, string $fieldType = 'text', array $options = []): mixed
    {
        return TemplateHelper::cmsField($fieldName, $fieldType, $options);
    }
}

if (! function_exists('cmsFieldVariableName')) {
    /**
     * Generate a variable name from a field name
     *
     * Converts the full dot-notated field name to camelCase
     * Example: 'features.items' -> 'featuresItems'
     * Example: 'hero.cta_buttons' -> 'heroCtaButtons'
     *
     * @param  string  $fieldName  The field name (supports dot notation)
     * @return string The variable name (camelCase)
     */
    function cmsFieldVariableName(string $fieldName): string
    {
        // Replace dots with spaces, then split on spaces, underscores, and hyphens
        $normalized = str_replace('.', ' ', $fieldName);
        $parts = preg_split('/[\s_-]+/', $normalized);

        // First part stays lowercase, rest are capitalized
        $camelCase = array_shift($parts);
        foreach ($parts as $part) {
            $camelCase .= ucfirst($part);
        }

        return $camelCase;
    }
}
