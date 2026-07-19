<?php

namespace FrankenCms\Services;

use Exception;
use FrankenCms\Settings\AiSettings;
use FrankenCms\Settings\MediaSettings;
use Laravel\Ai\Image;
use Laravel\Ai\Responses\ImageResponse;

class AiImageService
{
    /**
     * Aspect ratios the SDK accepts directly; everything else maps to 16:9
     */
    protected const SUPPORTED_ASPECTS = ['16:9', '4:3', '1:1', '3:2'];

    /**
     * Generate a featured image from a prompt
     *
     * @throws Exception
     */
    public function generate(string $prompt): ImageResponse
    {
        if (! AiFeatureDetector::isImageAvailable()) {
            throw new Exception('AI image generation is not available. Configure an image-capable provider (e.g. OPENAI_API_KEY) and enable it in settings.');
        }

        $settings = app(AiSettings::class);

        $selected = $settings->image_provider;

        if ($selected && ! array_key_exists($selected, AiFeatureDetector::imageCapableProviders())) {
            throw new Exception("The selected image provider [{$selected}] is not configured. Update the AI settings or set its API key in your .env.");
        }

        $provider = $selected ?? $this->fallbackProvider();

        try {
            return Image::of($prompt)
                ->size($this->aspectSize())
                ->quality($settings->image_quality)
                ->generate(
                    provider: $provider,
                    model: $selected ? $settings->image_model : null,
                );
        } catch (Exception $e) {
            throw new Exception('AI image generation failed: ' . $e->getMessage());
        }
    }

    /**
     * The SDK's default_for_images provider may not have credentials even
     * when the feature is available (e.g. the SDK defaults images to gemini
     * while only OPENAI_API_KEY is set). Route to a configured image-capable
     * provider in that case; null lets the SDK use its own default.
     */
    protected function fallbackProvider(): ?string
    {
        $default = config('ai.default_for_images');

        if ($default && array_key_exists($default, AiFeatureDetector::imageCapableProviders())) {
            return null;
        }

        return array_key_first(AiFeatureDetector::imageCapableProviders());
    }

    /**
     * The SDK size string matching the configured featured aspect ratio
     */
    public function aspectSize(): string
    {
        $ratio = app(MediaSettings::class)->featured_aspect_ratio;

        return in_array($ratio, self::SUPPORTED_ASPECTS, true) ? $ratio : '16:9';
    }

    /**
     * Probe a provider/model pair by generating one minimal image, throwing
     * with the provider's actual reason on failure. Defaults to the saved
     * image engine settings (including the auto-fallback provider).
     * Note: a successful probe generates one small billable image.
     *
     * @throws Exception
     */
    public function verifyImageModel(?string $provider = null, ?string $model = null): void
    {
        if (! AiFeatureDetector::isInstalled()) {
            throw new Exception('The laravel/ai SDK is not installed.');
        }

        $selected = $provider ?? app(AiSettings::class)->image_provider;

        if ($selected && ! array_key_exists($selected, AiFeatureDetector::imageCapableProviders())) {
            throw new Exception("Image provider [{$selected}] is not configured. Set its API key in your .env.");
        }

        if (! $selected && empty(AiFeatureDetector::imageCapableProviders())) {
            throw new Exception('No image-capable provider is configured. Add e.g. OPENAI_API_KEY to your .env.');
        }

        $response = Image::of('A single small blue circle on a plain white background.')
            ->size('1:1')
            ->quality('low')
            ->generate(
                provider: $selected ?? $this->fallbackProvider(),
                model: $selected ? $model : null,
            );

        if (trim($response->firstImage()->image) === '') {
            throw new Exception('The model responded but returned no image data.');
        }
    }
}
