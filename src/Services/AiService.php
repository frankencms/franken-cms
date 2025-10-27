<?php

namespace FrankenCms\Services;

use Exception;
use FrankenCms\Prompts\PromptManager;
use FrankenCms\Settings\AiSettings;
use Prism\Prism\Prism;
use Prism\Prism\ValueObjects\Media\Image;

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
            throw new Exception('AI features are not available. Please install prism-php/prism and configure Igor in settings.');
        }

        // Get prompt configuration
        $promptConfig = $this->promptManager->getPrompt($actionKey);

        // Check if this is a vision prompt with an image
        $imageUrl = $context['image_url'] ?? null;
        $imagePath = $context['image_path'] ?? null;
        $isVisionPrompt = ($promptConfig['supports_vision'] ?? false) && ($imageUrl || $imagePath);

        // Remove image data from context so it doesn't get into the text prompt
        unset($context['image_url'], $context['image_path']);

        // Format prompt with context variables
        $formattedPrompt = $this->promptManager->formatPrompt(
            $promptConfig['prompt'],
            $context
        );

        // Get AI settings
        $settings = app(AiSettings::class);

        try {
            // Call Prism to generate content
            $prismRequest = Prism::text()
                ->using($settings->provider, $settings->model)
                ->withMaxTokens($promptConfig['max_tokens'] ?? 500);

            // Add prompt with optional image
            if ($isVisionPrompt) {
                // Production: Use publicly accessible URL
                if ($imageUrl && ! app()->environment('local')) {
                    $prismRequest->withPrompt(
                        $formattedPrompt,
                        [Image::fromUrl(url: $imageUrl)]
                    );
                }
                // Local development: Use base64 encoding from local file
                elseif ($imagePath && file_exists($imagePath) && app()->environment('local')) {
                    $imageData = base64_encode(file_get_contents($imagePath));
                    $mimeType = mime_content_type($imagePath);

                    $prismRequest->withPrompt(
                        $formattedPrompt,
                        [Image::fromBase64(base64: $imageData, mimeType: $mimeType)]
                    );
                } else {
                    // Fallback to text-only if image not accessible
                    $prismRequest->withPrompt($formattedPrompt);
                }
            } else {
                $prismRequest->withPrompt($formattedPrompt);
            }

            // Only set temperature if configured in the prompt
            if (isset($promptConfig['temperature'])) {
                $prismRequest->usingTemperature($promptConfig['temperature']);
            }

            // Handle streaming if callback provided
            if ($streamCallback !== null) {
                $fullText = '';
                $stream = $prismRequest->asStream();

                foreach ($stream as $event) {
                    // Only process TextDeltaEvent for content chunks
                    if ($event instanceof \Prism\Prism\Streaming\Events\TextDeltaEvent) {
                        $fullText .= $event->delta;
                        $streamCallback($event->delta);
                    }
                }

                return trim($fullText);
            }

            // Non-streaming generation
            $response = $prismRequest->generate();

            return trim($response->text);
        } catch (Exception $e) {
            throw new Exception('AI generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Test provider connection
     */
    public function testConnection(): bool
    {
        if (! AiFeatureDetector::isPrismInstalled()) {
            return false;
        }

        try {
            $settings = app(AiSettings::class);

            if (empty($settings->api_key) && $settings->provider !== 'ollama') {
                return false;
            }

            $response = Prism::text()
                ->using($settings->provider, $settings->model)
                ->withPrompt('Respond with only the word "OK"')
                ->withMaxTokens(10)
                ->generate();

            return ! empty($response->text);
        } catch (Exception $e) {
            return false;
        }
    }
}
