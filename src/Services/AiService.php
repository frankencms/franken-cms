<?php

namespace FrankenCms\Services;

use Exception;
use FrankenCms\Ai\CmsAgent;
use FrankenCms\Prompts\PromptManager;
use FrankenCms\Settings\AiSettings;
use Laravel\Ai\Files\Image;
use Laravel\Ai\Streaming\Events\TextDelta;

class AiService
{
    public function __construct(
        protected PromptManager $promptManager
    ) {}

    /**
     * Generate content using AI with optional streaming
     *
     * @param  callable|null  $streamCallback  Optional callback for streaming (receives string chunks)
     *
     * @throws Exception
     */
    public function generate(string $actionKey, array $context, ?callable $streamCallback = null): string
    {
        if (! AiFeatureDetector::isAvailable()) {
            throw new Exception('AI features are not available. Install laravel/ai, set a provider API key in your .env, and enable Igor in settings.');
        }

        $settings = app(AiSettings::class);

        if (! array_key_exists($settings->text_provider, AiFeatureDetector::configuredProviders())) {
            throw new Exception("The selected AI text provider [{$settings->text_provider}] is not configured. Set its API key in your .env or choose a configured provider in settings.");
        }

        $promptConfig = $this->promptManager->getPrompt($actionKey);

        $imageUrl = $context['image_url'] ?? null;
        $imagePath = $context['image_path'] ?? null;
        $isVisionPrompt = ($promptConfig['supports_vision'] ?? false) && ($imageUrl || $imagePath);

        unset($context['image_url'], $context['image_path']);

        $formattedPrompt = $this->promptManager->formatPrompt(
            $promptConfig['prompt'],
            $context
        );

        $agent = new CmsAgent(
            maxTokens: $promptConfig['max_tokens'] ?? 500,
            temperature: $promptConfig['temperature'] ?? null,
        );

        $attachments = $isVisionPrompt ? $this->buildImageAttachments($imageUrl, $imagePath) : [];

        try {
            if ($streamCallback !== null) {
                $fullText = '';
                $stream = $agent->stream(
                    $formattedPrompt,
                    $attachments,
                    provider: $settings->text_provider,
                    model: $settings->text_model,
                );

                foreach ($stream as $event) {
                    if ($event instanceof TextDelta) {
                        $fullText .= $event->delta;
                        $streamCallback($event->delta);
                    }
                }

                return $this->guardAgainstEmptyResult(trim($fullText));
            }

            $response = $agent->prompt(
                $formattedPrompt,
                $attachments,
                provider: $settings->text_provider,
                model: $settings->text_model,
            );

            return $this->guardAgainstEmptyResult(trim($response->text));
        } catch (Exception $e) {
            throw new Exception('AI generation failed: ' . $e->getMessage());
        }
    }

    /**
     * An empty result is a failure, not a success — typically a reasoning
     * model exhausting the token cap before producing output, or a model
     * incompatible with the request.
     *
     * @throws Exception
     */
    protected function guardAgainstEmptyResult(string $text): string
    {
        if ($text === '') {
            throw new Exception('the model returned no content. Try a different model, or check that your provider project has access to the selected one.');
        }

        return $text;
    }

    /**
     * Probe a provider/model pair with a tiny prompt, throwing with the
     * provider's actual reason on failure. Defaults to the saved text
     * engine settings when no pair is given.
     *
     * @throws Exception
     */
    public function verifyTextModel(?string $provider = null, ?string $model = null): void
    {
        if (! AiFeatureDetector::isInstalled()) {
            throw new Exception('The laravel/ai SDK is not installed.');
        }

        $settings = app(AiSettings::class);
        $provider ??= $settings->text_provider;
        $model ??= $settings->text_model;

        if (! array_key_exists($provider, AiFeatureDetector::configuredProviders())) {
            throw new Exception("Provider [{$provider}] is not configured. Set its API key in your .env.");
        }

        $response = (new CmsAgent(maxTokens: 2000))->prompt(
            'Respond with only the word "OK"',
            provider: $provider,
            model: $model,
        );

        if (trim($response->text) === '') {
            throw new Exception('The model responded but returned no content.');
        }
    }

    /**
     * Build SDK image attachments, preferring a public URL outside local dev
     *
     * @return array<int, Image>
     */
    protected function buildImageAttachments(?string $imageUrl, ?string $imagePath): array
    {
        if ($imageUrl && ! app()->environment('local')) {
            return [Image::fromUrl($imageUrl)];
        }

        if ($imagePath && file_exists($imagePath)) {
            return [Image::fromPath($imagePath)];
        }

        return [];
    }
}
