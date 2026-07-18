<?php

namespace FrankenCms\Services;

use Exception;
use FrankenCms\Settings\AiSettings;
use Illuminate\Support\Str;
use Laravel\Ai\Ai;

class AiFeatureDetector
{
    /**
     * Check if AI features are available (installed, configured, and enabled)
     */
    public static function isAvailable(): bool
    {
        if (! self::isInstalled()) {
            return false;
        }

        if (empty(self::configuredProviders())) {
            return false;
        }

        try {
            return app(AiSettings::class)->enabled;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Check if the laravel/ai SDK is installed
     */
    public static function isInstalled(): bool
    {
        return class_exists(Ai::class);
    }

    /**
     * Providers from config/ai.php that have credentials configured.
     * Ollama needs no key, so it is opt-in via franken-cms.ai.enable_ollama.
     *
     * @return array<string, string> provider name => display label
     */
    public static function configuredProviders(): array
    {
        return collect(config('ai.providers', []))
            ->filter(function (array $provider, string $name) {
                if (($provider['driver'] ?? null) === 'ollama') {
                    return (bool) config('franken-cms.ai.enable_ollama', false);
                }

                return ! empty($provider['key']);
            })
            ->mapWithKeys(fn (array $provider, string $name) => [$name => Str::title($name)])
            ->all();
    }
}
