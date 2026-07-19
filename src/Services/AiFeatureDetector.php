<?php

namespace FrankenCms\Services;

use Exception;
use FrankenCms\Settings\AiSettings;
use Illuminate\Support\Str;
use Laravel\Ai\Ai;

class AiFeatureDetector
{
    /**
     * Providers whose laravel/ai gateway supports image generation
     * (classes implementing Laravel\Ai\Contracts\Providers\ImageProvider)
     */
    protected const IMAGE_CAPABLE_DRIVERS = ['openai', 'gemini', 'azure', 'bedrock', 'xai', 'openrouter'];

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

    /**
     * Configured providers that support image generation
     *
     * @return array<string, string> provider name => display label
     */
    public static function imageCapableProviders(): array
    {
        return collect(self::configuredProviders())
            ->filter(function (string $label, string $name) {
                $driver = config("ai.providers.{$name}.driver", $name);

                return in_array($driver, self::IMAGE_CAPABLE_DRIVERS, true);
            })
            ->all();
    }

    /**
     * Check if featured image generation is available
     */
    public static function isImageAvailable(): bool
    {
        return self::isAvailable() && ! empty(self::imageCapableProviders());
    }
}
