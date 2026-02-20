<?php

namespace FrankenCms\Services;

use Exception;
use FrankenCms\Settings\AiSettings;

class AiFeatureDetector
{
    /**
     * Check if AI features are available
     */
    public static function isAvailable(): bool
    {
        // Check if Prism is installed
        if (! self::isPrismInstalled()) {
            return false;
        }

        // Check if enabled in settings
        try {
            $settings = app(AiSettings::class);

            return $settings->enabled && ! empty($settings->api_key);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check if Prism PHP package is installed
     */
    public static function isPrismInstalled(): bool
    {
        return app()->providerIsLoaded('Prism\\Prism\\PrismServiceProvider');
    }
}
