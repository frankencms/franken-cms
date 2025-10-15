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
