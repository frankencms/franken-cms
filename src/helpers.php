<?php

use FrankenCms\Helpers\TemplateHelper;
use FrankenCms\Services\FaviconGenerator;
use Illuminate\Support\Facades\View;

if (! function_exists('_renderCmsField')) {
    /**
     * Internal: Render a CMS field value from the current page (called by blade directive)
     *
     * @param  string  $fieldName  The field name (supports dot notation)
     * @param  string  $fieldType  The field type (text, textarea, repeater, etc.)
     * @param  array  $options  Additional options (not used for rendering, only for admin)
     * @return mixed The rendered field value
     */
    function _renderCmsField(string $fieldName, string $fieldType = 'text', array $options = []): mixed
    {
        return TemplateHelper::cmsField($fieldName, $fieldType, $options);
    }
}

if (! function_exists('cmsField')) {
    /**
     * Retrieve a CMS field value from the $cmsFields collection
     *
     * Supports both camelCase and dot notation:
     * - cmsField('heroTitle')
     * - cmsField('hero.title')
     *
     * @param  string  $fieldName  The field name (camelCase or dot notation)
     * @return mixed The field value or null if not found
     */
    function cmsField(string $fieldName): mixed
    {
        // Get the cmsFields collection from view data
        $cmsFields = View::shared('cmsFields') ?? collect();

        // Try camelCase version first
        $camelCaseName = cmsFieldVariableName($fieldName);
        if ($cmsFields->has($camelCaseName)) {
            return $cmsFields->get($camelCaseName);
        }

        // Try original name as fallback
        if ($cmsFields->has($fieldName)) {
            return $cmsFields->get($fieldName);
        }

        return null;
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

if (! function_exists('setting')) {
    /**
     * Retrieve a setting value from any settings class
     *
     * Usage:
     * - setting('general.title')
     * - setting('seo.site_name')
     * - setting('reading.posts_per_page')
     * - setting('general.title', 'Default Title')
     *
     * @param  string  $key  The setting key in format 'group.property'
     * @param  mixed  $default  Default value if setting not found
     * @return mixed The setting value or default
     */
    function setting(string $key, mixed $default = null): mixed
    {
        // Parse the key into group and property
        $parts = explode('.', $key, 2);

        if (count($parts) !== 2) {
            return $default;
        }

        [$group, $property] = $parts;

        // Map short group names to settings classes
        $settingsMap = [
            'general' => \FrankenCms\Settings\GeneralSettings::class,
            'reading' => \FrankenCms\Settings\ReadingSettings::class,
            'seo' => \FrankenCms\Settings\SeoSettings::class,
            'media' => \FrankenCms\Settings\MediaSettings::class,
            'permalink' => \FrankenCms\Settings\PermalinkSettings::class,
            'robots' => \FrankenCms\Settings\RobotsSettings::class,
            'sitemap' => \FrankenCms\Settings\SitemapSettings::class,
        ];

        // Check if the group exists
        if (! isset($settingsMap[$group])) {
            return $default;
        }

        try {
            // Resolve the settings class
            $settings = app($settingsMap[$group]);

            // Return the property value if it exists
            if (property_exists($settings, $property)) {
                return $settings->{$property};
            }

            return $default;
        } catch (\Exception $e) {
            logger()->error("Failed to retrieve setting '{$key}': " . $e->getMessage());
            return $default;
        }
    }
}

if (! function_exists('favicon_tags')) {
    /**
     * Get HTML tags for favicons
     *
     * @return string HTML meta tags for favicons
     */
    function favicon_tags(): string
    {
        return app(FaviconGenerator::class)->getHtmlTags();
    }
}


if (!function_exists('aspect_ratio')) {
    /**
     * Calculate and return a simplified aspect ratio (e.g. "16:9").
     *
     * @param  int|float  $width
     * @param  int|float  $height
     * @return string
     */
    function aspect_ratio($width, $height): string
    {
        if ($width == 0 || $height == 0) {
            return 'Invalid dimensions';
        }

        $gcd = function ($a, $b) use (&$gcd) {
            return $b == 0 ? $a : $gcd($b, fmod($a, $b));
        };

        $divisor = $gcd($width, $height);

        $ratioWidth = $width / $divisor;
        $ratioHeight = $height / $divisor;

        return sprintf('%d:%d', $ratioWidth, $ratioHeight);
    }
}
