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
            'general'   => \FrankenCms\Settings\GeneralSettings::class,
            'reading'   => \FrankenCms\Settings\ReadingSettings::class,
            'seo'       => \FrankenCms\Settings\SeoSettings::class,
            'media'     => \FrankenCms\Settings\MediaSettings::class,
            'permalink' => \FrankenCms\Settings\PermalinkSettings::class,
            'robots'    => \FrankenCms\Settings\RobotsSettings::class,
            'sitemap'   => \FrankenCms\Settings\SitemapSettings::class,
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

if (! function_exists('aspect_ratio')) {
    /**
     * Calculates the aspect ratio of a given width and height value.
     *
     * This function computes the greatest common divisor (GCD) of the width and height
     * to derive a whole number ratio. Optionally, if `$cleanRatio` is set to true,
     * it matches the calculated decimal ratio to the closest common aspect ratio.
     */
    function aspect_ratio(float | int $width, float | int $height, bool $cleanRatio = false): string
    {
        // Calculate GCD (Greatest Common Divisor) using Euclidean algorithm
        $gcd = function (int | float $a, int | float $b) use (&$gcd): int | float {
            return $b ? $gcd($b, $a % $b) : $a;
        };

        $divisor = $gcd($width, $height);

        // Calculate exact whole number ratio
        $ratioWidth = $width / $divisor;
        $ratioHeight = $height / $divisor;

        // Calculate decimal ratio
        $decimal = round($width / $height, 2);

        if ($cleanRatio) {
            // Common aspect ratios to match against
            $commonRatios = [
                ['ratio' => '1:1', 'decimal' => 1.0],      // Square - Instagram posts, profile pictures
                ['ratio' => '4:3', 'decimal' => 1.33],     // Traditional TV, iPad, some cameras
                ['ratio' => '3:2', 'decimal' => 1.5],      // 35mm film, DSLR cameras, print photos
                ['ratio' => '16:10', 'decimal' => 1.6],    // Older widescreen monitors, some laptops
                ['ratio' => '16:9', 'decimal' => 1.78],    // HD video, modern TV, YouTube, most monitors
                ['ratio' => '1.85:1', 'decimal' => 1.85],  // Common cinema/movie format
                ['ratio' => '2:1', 'decimal' => 2.0],      // Univisium, some social media covers
                ['ratio' => '21:9', 'decimal' => 2.33],    // Ultrawide monitors, cinematic gaming
                ['ratio' => '2.39:1', 'decimal' => 2.39],  // Anamorphic widescreen cinema (CinemaScope)
                ['ratio' => '3:1', 'decimal' => 3.0],      // Panoramic images, ultra-wide displays
            ];

            // Find closest common ratio
            $closest = $commonRatios[0]['ratio'];
            $smallestDiff = PHP_FLOAT_MAX;

            foreach ($commonRatios as $common) {
                $diff = abs($decimal - $common['decimal']);
                if ($diff < $smallestDiff) {
                    $smallestDiff = $diff;
                    $closest = $common['ratio'];
                }
            }

            return $closest;
        }

        // Return precise decimal ratio
        return $decimal . ':1';
    }
}

if (! function_exists('frankenField')) {
    /**
     * Get raw field data from current page's custom fields
     *
     * Returns unrendered/processed data:
     * - Simple fields: raw string/number/boolean
     * - Tags: array of tag strings
     * - Repeaters: Collection of items (cleaned structure)
     * - Media: media object or URL
     *
     * Accepts both dot notation ('hero.tags') and camelCase ('heroTags')
     *
     * @param  string  $fieldName  Field name (supports dot notation or camelCase)
     * @return mixed Raw field value
     */
    function frankenField(string $fieldName): mixed
    {
        // First, check if the field exists in the $frankenFields collection
        // (this is where rendered fields are cached)
        $frankenFields = View::shared('frankenFields');

        if ($frankenFields && $frankenFields instanceof \Illuminate\Support\Collection) {
            // Try camelCase version first (e.g., 'hero.tags' -> 'heroTags')
            $camelCaseName = cmsFieldVariableName($fieldName);
            if ($frankenFields->has($camelCaseName)) {
                return $frankenFields->get($camelCaseName);
            }

            // Try original name as-is
            if ($frankenFields->has($fieldName)) {
                return $frankenFields->get($fieldName);
            }
        }

        // If not in collection, fall back to fetching from custom_fields
        $currentPage = app(\FrankenCms\Services\CurrentPageService::class)->getPage();

        if (! $currentPage) {
            return null;
        }

        $value = data_get($currentPage->custom_fields, $fieldName);

        // For repeaters, apply cleaning (remove custom_fields nesting)
        if (is_array($value) && ! empty($value)) {
            $first = reset($value);
            if (is_array($first) && isset($first['custom_fields'])) {
                // It's a repeater - clean the structure
                return collect($value)->map(function ($item) {
                    if (is_array($item) && isset($item['custom_fields'])) {
                        return (object) array_merge($item, $item['custom_fields']);
                    }

                    return (object) $item;
                });
            }
        }

        return $value;
    }
}

if (! function_exists('franken_field')) {
    /**
     * Get a raw CMS field value (snake_case alias for frankenField)
     *
     * @param  string  $fieldName  The field name in dot notation
     * @return mixed Raw field value
     */
    function franken_field(string $fieldName): mixed
    {
        return frankenField($fieldName);
    }
}

if (! function_exists('_parseFieldExpression')) {
    /**
     * Parse field directive expression into name and options
     *
     * @internal Used by Blade directives
     *
     * @param  string  $fieldName  Field name
     * @param  array  $options  Field options
     * @return array{name: string, options: array}
     */
    function _parseFieldExpression(string $fieldName, array $options = []): array
    {
        return ['name' => $fieldName, 'options' => $options];
    }
}
